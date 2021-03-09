<?php

declare(strict_types=1);

use Mautic\Auth\ApiAuth;
use Mautic\MauticApi;

require __DIR__.'/vendor/autoload.php';

if ('cli' !== php_sapi_name()) {
    die('This script can only run on the command line');
}

$vars = [
    1 => 'instanceUrl',
    2 => 'username',
    3 => 'password',
    4 => 'mauticVersion',
    5 => 'assetCategoryId',
    6 => 'fileToUpload',
];

foreach ($vars as $id => $var) {
    if (empty($_SERVER['argv'][$id])) {
        echo "Argument ${id} (${var}) is missing. Run this script as \"php upload.php ".implode(' ', $vars)."\"\n";
        exit(1);
    }

    $$var = $_SERVER['argv'][$id];
}

// Set up the authentication
$settings = [
    'userName'   => $username,
    'password'   => $password,
];

// Initiate the auth object specifying to use BasicAuth
$initAuth = new ApiAuth();
$auth     = $initAuth->newAuth($settings, 'BasicAuth');
$api      = new MauticApi();

/** @var \Mautic\Api\Files */
$filesApi = $api->newApi('files', $auth, $instanceUrl);

/** @var \Mautic\Api\Assets */
$assetApi = $api->newApi('assets', $auth, $instanceUrl);

/**
 * Upload the file.
 */
$filesApi->setFolder('assets');
// File should be an absolute path!
$fileRequest = [
    'file' => $fileToUpload,
];

$response = $filesApi->create($fileRequest);

if (isset($response['error'])) {
    echo $response['error']['code'].': '.$response['error']['message']."\n";
    exit(1);
}

if (!isset($response['file']) || !isset($response['file']['name'])) {
    echo 'An unknown error occurred while uploading the release asset to our Mautic instance. '
        ."Please try again or debug locally (we don't provide logs in CI for security reasons)\n";
    exit(1);
}

/**
 * Create the actual asset based on the file we just uploaded.
 */
$data = [
    'title'           => "Mautic ${mauticVersion}",
    'storageLocation' => 'local',
    'file'            => $response['file']['name'],
    'category'        => $assetCategoryId,
    'isPublished'     => true,
];

$response = $assetApi->create($data);

if (isset($response['error'])) {
    echo $response['error']['code'].': '.$response['error']['message']."\n";
    exit(1);
}

if (!isset($response['asset']['id']) || !isset($response['asset']['downloadUrl'])) {
    echo "Unknown error occurred while creating asset. Please debug locally.\n";
    exit(1);
}

echo 'Successfully created asset with ID '.$response['asset']['id']." 🚀\n";
