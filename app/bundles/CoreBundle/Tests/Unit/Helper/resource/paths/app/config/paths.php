<?php

if (!isset($root)) {
    $root = __DIR__;
}

$paths = [
    //customizable
    'themes'       => 'themes',
    'assets'       => 'media',
    'asset_prefix' => '',
    'plugins'      => 'plugins',
    'translations' => 'translations',
    'local_config' => __DIR__.'/local.php',
];

//allow easy overrides of the above
if (file_exists(__DIR__.'/paths_local.php')) {
    include __DIR__.'/paths_local.php';
}

//fixed
$paths = array_merge($paths, [
    //remove /app from the root
    'root'    => substr($root, 0, -4),
    'app'     => 'app',
    'bundles' => 'app/bundles',
    'vendor'  => 'vendor',
]);
