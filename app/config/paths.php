<?php

$paths = [
    // customizable
    'themes'       => 'themes',
    'assets'       => 'app/assets',
    'media'        => 'media',
    'asset_prefix' => '',
    'plugins'      => 'plugins',
    'translations' => 'translations',
    'local_config' => '%kernel.project_dir%/config/local.php',

/**
 * Webroot override for recommended-project installations where the webroot
 * (docroot/ or public/) is a subdirectory of the project root.
 * This is automatically detected from composer.json's:
 *   - extra.mautic-scaffold.locations.web-root (used by mautic/recommended-project)
 *   - extra.public-dir (Symfony convention)
 * Set explicitly here or in paths_local.php if auto-detection fails:
 */
    // 'local_root' => '%kernel.project_dir%/docroot',
];

$root ??= realpath(__DIR__.'/..');
$projectRoot ??= Mautic\CoreBundle\Loader\ParameterLoader::getProjectDirByRoot($root);

// allow easy overrides of the above
if (file_exists($projectRoot.'/config/paths_local.php')) {
    include $projectRoot.'/config/paths_local.php';
} elseif (file_exists($root.'/config/paths_local.php')) {
    include $root.'/config/paths_local.php';
}

// fixed
$paths = array_merge($paths, [
    // remove /app from the root
    'root'    => substr($root, 0, -4),
    'app'     => 'app',
    'bundles' => 'app/bundles',
    'vendor'  => 'vendor',
]);
