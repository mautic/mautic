<?php

$this->import('security.php');

// Support HTTP basic auth for test logins
$firewalls                       = $container->getExtensionConfig('security')[0]['firewalls'];
$firewalls['main']['http_basic'] = true;
$container->loadFromExtension('security',
    [
        'firewalls' => $firewalls,
    ]
);
