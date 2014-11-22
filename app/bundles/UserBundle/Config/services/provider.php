<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\Parameter;

//authentication
$container->setDefinition(
    'mautic.user.manager',
    new Definition(
        'Doctrine\ORM\EntityManager',
        array('Mautic\UserBundle\Entity\User')
    )
)
    ->setFactoryService('doctrine')
    ->setFactoryMethod('getManagerForClass');

$container->setDefinition(
    'mautic.user.repository',
    new Definition(
        'Mautic\UserBundle\Entity\UserRepository',
        array('Mautic\UserBundle\Entity\User')
    )
)
    ->setFactoryService('mautic.user.manager')
    ->setFactoryMethod('getRepository');


$container->setDefinition(
    'mautic.permission.manager',
    new Definition(
        'Doctrine\ORM\EntityManager',
        array('Mautic\UserBundle\Entity\Permission')
    )
)
    ->setFactoryService('doctrine')
    ->setFactoryMethod('getManagerForClass');


$container->setDefinition(
    'mautic.permission.repository',
    new Definition(
        'Mautic\UserBundle\Entity\PermissionRepository',
        array('Mautic\UserBundle\Entity\Permission')
    )
)
    ->setFactoryService('mautic.permission.manager')
    ->setFactoryMethod('getRepository');


$container->setDefinition(
    'mautic.user.provider',
    new Definition(
        'Mautic\UserBundle\Security\Provider\UserProvider',
        array(
            new Reference('mautic.user.repository'),
            new Reference('mautic.permission.repository'),
            new Reference('session')
        )
    )
);


/*
$container->setDefinition(
    'mautic_user.example',
    new Definition(
        'Mautic\UserBundle\Example',
        array(
            new Reference('service_id'),
            "plain_value",
            new Parameter('parameter_name'),
        )
    )
);

*/