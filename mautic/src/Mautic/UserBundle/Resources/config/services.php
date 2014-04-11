<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
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
            new Reference('mautic.permission.repository')
        )
    )
);

//User forms
$container->setDefinition(
    'mautic.form.type.user',
        new Definition(
        'Mautic\UserBundle\Form\Type\UserType',
        array(
            new Reference("service_container"),
            new Reference('security.context'),
            "%mautic.bundles%"
        )
    )
)
    ->addTag('form.type', array(
        'alias' => 'user',
    ));

//Role form
$container->setDefinition(
    'mautic.form.type.role',
    new Definition(
        'Mautic\UserBundle\Form\Type\RoleType',
        array(
            new Reference("service_container"),
            new Reference('doctrine.orm.entity_manager'),
            "%mautic.bundles%"
        )
    )
)
    ->addTag('form.type', array(
        'alias' => 'role',
    ));

//Permission form
$container->setDefinition(
    'mautic.form.type.permissions',
    new Definition(
        'Mautic\UserBundle\Form\Type\PermissionsType',
        array(
            new Reference("service_container"),
            new Reference('doctrine.orm.entity_manager'),
            "%mautic.bundles%"
        )
    )
)
    ->addTag('form.type', array(
        'alias' => 'permissions',
    ));

//User model
$container->setDefinition(
    'mautic.model.user',
    new Definition(
        'Mautic\UserBundle\Model\UserModel',
        array(
            new Reference('service_container'),
            new Reference('request_stack'),
            new Reference('doctrine.orm.entity_manager'),
        )
    )
);

//Role model
$container->setDefinition(
    'mautic.model.role',
    new Definition(
        'Mautic\UserBundle\Model\RoleModel',
        array(
            new Reference("service_container"),
            new Reference('request_stack'),
            new Reference('doctrine.orm.entity_manager'),
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