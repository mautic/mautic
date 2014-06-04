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

//User forms
$container->setDefinition(
    'mautic.form.type.user',
    new Definition(
        'Mautic\UserBundle\Form\Type\UserType',
        array(
            new Reference("translator"),
            new Reference('doctrine.orm.entity_manager'),
            '%mautic.supported_languages%'
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
            new Reference("translator"),
            new Reference('doctrine.orm.entity_manager')
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
            new Reference("translator"),
            new Reference('mautic.security')
        )
    )
)
    ->addTag('form.type', array(
        'alias' => 'permissions',
    ));
