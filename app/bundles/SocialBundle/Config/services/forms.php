<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

use Symfony\Component\DependencyInjection\Definition;

//Social media forms
$container->setDefinition(
    'mautic.form.type.social.config',
    new Definition('Mautic\SocialBundle\Form\Type\ConfigType'))
    ->addTag('form.type', array(
        'alias' => 'socialmedia_config',
    ));

$container->setDefinition(
    'mautic.form.type.social.details',
    new Definition('Mautic\SocialBundle\Form\Type\DetailsType'))
    ->addTag('form.type', array(
        'alias' => 'socialmedia_details',
    ));

$container->setDefinition(
    'mautic.form.type.social.settings',
    new Definition('Mautic\SocialBundle\Form\Type\FeatureSettingsType',
        array(new \Symfony\Component\DependencyInjection\Reference('mautic.factory'))
    ))
    ->addTag('form.type', array(
        'alias' => 'socialmedia_featuresettings',
    ));

$container->setDefinition(
    'mautic.form.type.social.fields',
    new Definition('Mautic\SocialBundle\Form\Type\FieldsType'))
    ->addTag('form.type', array(
        'alias' => 'socialmedia_fields',
    ));

$container->setDefinition(
    'mautic.form.type.social.keys',
    new Definition('Mautic\SocialBundle\Form\Type\KeysType'))
    ->addTag('form.type', array(
        'alias' => 'socialmedia_keys',
    ));

$container->setDefinition(
    'mautic.form.type.social.services',
    new Definition('Mautic\SocialBundle\Form\Type\ServicesType'))
    ->addTag('form.type', array(
        'alias' => 'socialmedia_services',
    ));

$container->setDefinition(
    'mautic.form.type.social.facebook',
    new Definition('Mautic\SocialBundle\Form\Type\FacebookType'))
    ->addTag('form.type', array(
        'alias' => 'socialmedia_facebook',
    ));

$container->setDefinition(
    'mautic.form.type.social.twitter',
    new Definition('Mautic\SocialBundle\Form\Type\TwitterType'))
    ->addTag('form.type', array(
        'alias' => 'socialmedia_twitter',
    ));

$container->setDefinition(
    'mautic.form.type.social.googleplus',
    new Definition('Mautic\SocialBundle\Form\Type\GooglePlusType'))
    ->addTag('form.type', array(
        'alias' => 'socialmedia_googleplus',
    ));

$container->setDefinition(
    'mautic.form.type.social.linkedin',
    new Definition('Mautic\SocialBundle\Form\Type\LinkedInType'))
    ->addTag('form.type', array(
        'alias' => 'socialmedia_linkedin',
    ));