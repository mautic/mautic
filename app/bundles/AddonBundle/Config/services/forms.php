<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

use Symfony\Component\DependencyInjection\Definition;

$container->setDefinition(
    'mautic.form.type.integration.details',
    new Definition('Mautic\AddonBundle\Form\Type\DetailsType'))
    ->addTag('form.type', array(
        'alias' => 'integration_details',
    ));

$container->setDefinition(
    'mautic.form.type.integration.settings',
    new Definition('Mautic\AddonBundle\Form\Type\FeatureSettingsType',
        array(new \Symfony\Component\DependencyInjection\Reference('mautic.factory'))
    ))
    ->addTag('form.type', array(
        'alias' => 'integration_featuresettings',
    ));

$container->setDefinition(
    'mautic.form.type.integration.fields',
    new Definition('Mautic\AddonBundle\Form\Type\FieldsType'))
    ->addTag('form.type', array(
        'alias' => 'integration_fields',
    ));

$container->setDefinition(
    'mautic.form.type.integration.keys',
    new Definition('Mautic\AddonBundle\Form\Type\KeysType'))
    ->addTag('form.type', array(
        'alias' => 'integration_keys',
    ));
