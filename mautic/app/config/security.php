<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

$container->loadFromExtension('security', array(
    'providers' => array(
        'administrator' => array(
            'entity' => array(
                'class' => 'MauticUserBundle:User',
            ),
        ),
    ),
    'encoders' => array(
        'Symfony\Component\Security\Core\User\User' => array(
            'algorithm'         => 'bcrypt',
            'iterations'        => 12,
        ),
        'Mautic\UserBundle\Entity\User' => array(
            'algorithm'         => 'bcrypt',
            'iterations'        => 12,
        ),
    ),
    'firewalls' => array(
        /*
        //for now don't block anything
        'general' => array(
            'pattern'   => '^/',
            'anonymous' => array(),
        ),*/
        'dev' => array(
            'pattern' => '^/(_(profiler|wdt)|css|images|js)/',
            'security' => true,
            'anonymous' => array()
        ),
        'login' => array(
            'pattern'   => '^/login$',
            'anonymous' => array()
        ),
        'main' => array(
            'pattern' => "^/",
            'form_login' => array(
                'csrf_provider' => 'form.csrf_provider'
            ),
            'logout' => array(),
            'remember_me' => array(
                'key'      => '%secret%',
                'lifetime' => 31536000, // 365 days in seconds
                'path'     => '/',
                'domain'   => '', // Defaults to the current domain from $_SERVER
            ),
        ),
    )
));