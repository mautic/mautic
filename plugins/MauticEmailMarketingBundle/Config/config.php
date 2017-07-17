<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

return [
    'name'        => 'Email Marketing',
    'description' => 'Enables integration with Mautic supported email marketing services.',
    'version'     => '1.0',
    'author'      => 'Mautic',

    'services' => [
        'forms' => [
            'mautic.form.type.emailmarketing.mailchimp' => [
                'class'     => 'MauticPlugin\MauticEmailMarketingBundle\Form\Type\MailchimpType',
                'arguments' => 'mautic.factory',
                'alias'     => 'emailmarketing_mailchimp',
            ],
            'mautic.form.type.emailmarketing.constantcontact' => [
                'class'     => 'MauticPlugin\MauticEmailMarketingBundle\Form\Type\ConstantContactType',
                'arguments' => 'mautic.factory',
                'alias'     => 'emailmarketing_constantcontact',
            ],
            'mautic.form.type.emailmarketing.icontact' => [
                'class'     => 'MauticPlugin\MauticEmailMarketingBundle\Form\Type\IcontactType',
                'arguments' => 'mautic.factory',
                'alias'     => 'emailmarketing_icontact',
            ],
        ],
        'integrations' => [
            'mautic.integration.constantcontact' => [
                'class'     => \MauticPlugin\MauticEmailMarketingBundle\Integration\ConstantContactIntegration::class,
                'arguments' => [

                ],
            ],
            'mautic.integration.icontact' => [
                'class'     => \MauticPlugin\MauticEmailMarketingBundle\Integration\IcontactIntegration::class,
                'arguments' => [

                ],
            ],
            'mautic.integration.mailchimp' => [
                'class'     => \MauticPlugin\MauticEmailMarketingBundle\Integration\MailchimpIntegration::class,
                'arguments' => [

                ],
            ],
        ],
    ],
];
