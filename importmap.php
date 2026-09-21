<?php

$appEntrypoint = file_exists(__DIR__.'/docroot/app/bundles/CoreBundle/Assets/app.js')
    ? './docroot/app/bundles/CoreBundle/Assets/app.js'
    : './app/bundles/CoreBundle/Assets/app.js';

return [
    'app' => [
        'path'       => $appEntrypoint,
        'entrypoint' => true,
    ],
];
