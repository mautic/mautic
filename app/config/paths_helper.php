<?php

/*
 * @copyright   2015 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

// Include path settings
$root = $container->getParameter('kernel.root_dir');

// Include local paths
include 'paths.php';

// Closure to replace %kernel.root_dir% placeholders
$replaceRootPlaceholder = function (&$value) use ($root, &$replaceRootPlaceholder) {
    if (is_array($value)) {
        foreach ($value as &$v) {
            $replaceRootPlaceholder($v);
        }
    } elseif (strpos($value, '%kernel.root_dir%') !== false) {
        $value = str_replace('%kernel.root_dir%', $root, $value);
    }
};

// Handle paths
$replaceRootPlaceholder($paths);
