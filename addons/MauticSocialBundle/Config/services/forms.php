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
    'mautic.form.type.social.facebook',
    new Definition('MauticAddon\MauticSocialBundle\Form\Type\FacebookType'))
    ->addTag('form.type', array(
        'alias' => 'socialmedia_facebook',
    ));

$container->setDefinition(
    'mautic.form.type.social.twitter',
    new Definition('MauticAddon\MauticSocialBundle\Form\Type\TwitterType'))
    ->addTag('form.type', array(
        'alias' => 'socialmedia_twitter',
    ));

$container->setDefinition(
    'mautic.form.type.social.googleplus',
    new Definition('MauticAddon\MauticSocialBundle\Form\Type\GooglePlusType'))
    ->addTag('form.type', array(
        'alias' => 'socialmedia_googleplus',
    ));

$container->setDefinition(
    'mautic.form.type.social.linkedin',
    new Definition('MauticAddon\MauticSocialBundle\Form\Type\LinkedInType'))
    ->addTag('form.type', array(
        'alias' => 'socialmedia_linkedin',
    ));
