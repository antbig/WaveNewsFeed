<?php
/**
 * Created by PhpStorm.
 * User: antbig
 * Date: 20/11/17
 * Time: 20:08
 */
use Google\Spreadsheet\DefaultServiceRequest;
use Google\Spreadsheet\ServiceRequestFactory;

header( 'content-type: text/html; charset=utf-8' );
require __DIR__.'/vendor/autoload.php';
putenv('GOOGLE_APPLICATION_CREDENTIALS='.__DIR__.'/ENSEAck-key.json');
$client = new Google_Client();
$client->useApplicationDefaultCredentials();

$client->setApplicationName("Something to do with my representatives");
$client->setScopes(['https://www.googleapis.com/auth/drive', 'https://spreadsheets.google.com/feeds']);

if ($client->isAccessTokenExpired()) {
    $client->refreshTokenWithAssertion();
}

$accessToken = $client->fetchAccessTokenWithAssertion()["access_token"];
$serviceRequest = new DefaultServiceRequest($accessToken);
ServiceRequestFactory::setInstance($serviceRequest);


$spreadsheet = (new Google\Spreadsheet\SpreadsheetService)
    ->getSpreadsheetFeed()
    ->getByTitle('ENSEACK News');

// Get the first worksheet (tab)
$worksheets = $spreadsheet->getWorksheetFeed()->getEntries();
$worksheet = $worksheets[0];

$listFeed = $worksheet->getListFeed();

$lowestDisplay = null;
$lowestEntry = null;

/** @var ListEntry */
foreach ($listFeed->getEntries() as $entry) {
    $representative = $entry->getValues();
    if ($representative["affichageencours"] == 1) {
        if ($lowestDisplay == null || $representative["déjàaffiché"] < $lowestDisplay["déjàaffiché"]) {
            $lowestDisplay = $representative;
            $lowestEntry = $entry;
        }
    }
}

$result = array();

if ($lowestDisplay == null) {
    $result["success"] = false;
} else {
    $result["success"] = true;
    $result["message"] = $lowestDisplay["texteàafficher"];
    $result["category"] = $lowestDisplay["categories"];
    $lowestDisplay["déjàaffiché"]++;
    $lowestEntry->update($lowestDisplay);
}
echo json_encode($result);