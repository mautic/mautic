<?php

$this->import('security.php');

// Support HTTP basic auth for test logins
$firewalls                       = $container->getExtensionConfig('security')[0]['firewalls'];
$firewalls['main']['http_basic'] = true;
$container->loadFromExtension('security',
    [
        'firewalls' => $firewalls,
        'encoders'  => [
          'Symfony\Component\Security\Core\User\User' => [
            'algorithm'        => 'md5',
            'encode_as_base64' => false,
            'iterations'       => 0,
          ],
          'Mautic\UserBundle\Entity\User' => [
            'algorithm'        => 'md5',
            'encode_as_base64' => false,
            'iterations'       => 0,
          ],
        ],
    ]
);
