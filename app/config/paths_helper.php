<?php

include 'paths.php';

// Closure to replace kernel.project_dir placeholders
$replaceRootPlaceholder = function (&$value) use ($projectRoot, &$replaceRootPlaceholder) {
    if (is_array($value)) {
        foreach ($value as &$v) {
            $replaceRootPlaceholder($v);
        }
    } elseif (false !== strpos($value, '%kernel.project_dir%')) {
        $value = str_replace('%kernel.project_dir%', $projectRoot, $value);
    }
};

// Handle paths
$replaceRootPlaceholder($paths);
