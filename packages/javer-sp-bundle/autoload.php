<?php

// if library is in dev environement with its own vendor, include its autoload
if (file_exists(__DIR__ . '/vendor')) {
    require_once __DIR__ . '/vendor/autoload.php';
} else {
    // if library is in vendor of another project, include the global autolaod
    require_once __DIR__ . '/../../autoload.php';
}
