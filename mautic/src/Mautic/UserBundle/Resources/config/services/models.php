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