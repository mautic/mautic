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

//User forms
$container->setDefinition(
    'mautic.form.type.user',
    new Definition(
        'Mautic\UserBundle\Form\Type\UserType',
        array(new Reference('mautic.factory'))
    )
)
    ->addTag('form.type', array(
        'alias' => 'user',
    ));

//Role form
$container->setDefinition(
    'mautic.form.type.role',
    new Definition(
        'Mautic\UserBundle\Form\Type\RoleType'
    )
)
    ->addTag('form.type', array(
        'alias' => 'role',
    ));

//Permission form
$container->setDefinition(
    'mautic.form.type.permissions',
    new Definition(
        'Mautic\UserBundle\Form\Type\PermissionsType'
    )
)
    ->addTag('form.type', array(
        'alias' => 'permissions',
    ));

$container->setDefinition(
    'mautic.form.type.permissionlist',
    new Definition(
        'Mautic\UserBundle\Form\Type\PermissionListType'
    )
)
    ->addTag('form.type', array(
        'alias' => 'permissionlist',
    ));

//Password reset
$container->setDefinition(
    'mautic.form.type.passwordreset',
    new Definition(
        'Mautic\UserBundle\Form\Type\PasswordResetType'
    )
)
    ->addTag('form.type', array(
        'alias' => 'passwordreset',
    ));