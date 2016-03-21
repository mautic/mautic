<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

return array(
    'name'        => 'Social Media',
    'description' => 'Enables integrations with Mautic supported social media services.',
    'version'     => '1.0',
    'author'      => 'Mautic',

    'services'    => array(
        'forms' => array(
            'mautic.form.type.social.facebook'   => array(
                'class' => 'MauticPlugin\MauticSocialBundle\Form\Type\FacebookType',
                'alias' => 'socialmedia_facebook'
            ),
            'mautic.form.type.social.twitter'    => array(
                'class' => 'MauticPlugin\MauticSocialBundle\Form\Type\TwitterType',
                'alias' => 'socialmedia_twitter'
            ),
            'mautic.form.type.social.googleplus' => array(
                'class' => 'MauticPlugin\MauticSocialBundle\Form\Type\GooglePlusType',
                'alias' => 'socialmedia_googleplus'
            ),
            'mautic.form.type.social.linkedin'   => array(
                'class' => 'MauticPlugin\MauticSocialBundle\Form\Type\LinkedInType',
                'alias' => 'socialmedia_linkedin'
            )
        )
    )
);