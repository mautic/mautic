<?php
declare(strict_types=1);

// Define base paths with defaults
$paths = [
    // customizable paths
    'themes'       => 'themes',
    'assets'       => 'app/assets',
    'media'        => 'media',
    'asset_prefix' => '',
    'plugins'      => 'plugins',
    'translations' => 'translations',
    'local_config' => '%kernel.project_dir%/config/local.php',
];

// Set root paths
$root = $root ?? realpath(__DIR__.'/..');
$projectRoot = $projectRoot ?? Mautic\CoreBundle\Loader\ParameterLoader::getProjectDirByRoot($root);

// Check for local path overrides (only check one location, prioritizing project root)
$localPathsFile = file_exists($projectRoot.'/config/paths_local.php') 
    ? $projectRoot.'/config/paths_local.php'
    : ($root.'/config/paths_local.php');

if (file_exists($localPathsFile)) {
    include $localPathsFile;
}

// Add fixed paths - these cannot be overridden
$paths += [
    'root'    => substr($root, 0, -4), // remove /app from the root
    'app'     => 'app',
    'bundles' => 'app/bundles',
    'vendor'  => 'vendor',
];
