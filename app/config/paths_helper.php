<?php

include 'paths.php';

// Closure to replace %kernel.root_dir% placeholders
$replaceRootPlaceholder = function (&$value) use ($root, &$replaceRootPlaceholder) {
    if (is_array($value)) {
        foreach ($value as &$v) {
            $replaceRootPlaceholder($v);
        }
    } elseif (false !== strpos($value, '%kernel.root_dir%')) {
        $value = str_replace('%kernel.root_dir%', $root, $value);
    }
};

// Handle paths
$replaceRootPlaceholder($paths);
