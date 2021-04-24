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
                'arguments' => ['mautic.helper.integration', 'mautic.plugin.model.plugin', 'session', 'mautic.helper.core_parameters'],
            ],
            'mautic.form.type.emailmarketing.constantcontact' => [
                'class'     => 'MauticPlugin\MauticEmailMarketingBundle\Form\Type\ConstantContactType',
                'arguments' => ['mautic.helper.integration', 'mautic.plugin.model.plugin', 'session', 'mautic.helper.core_parameters'],
            ],
            'mautic.form.type.emailmarketing.icontact' => [
                'class'     => 'MauticPlugin\MauticEmailMarketingBundle\Form\Type\IcontactType',
                'arguments' => ['mautic.helper.integration', 'mautic.plugin.model.plugin', 'session', 'mautic.helper.core_parameters'],
            ],
        ],
        'integrations' => [
            'mautic.integration.constantcontact' => [
                'class'     => \MauticPlugin\MauticEmailMarketingBundle\Integration\ConstantContactIntegration::class,
                'arguments' => [
                    'event_dispatcher',
                    'mautic.helper.cache_storage',
                    'doctrine.orm.entity_manager',
                    'session',
                    'request_stack',
                    'router',
                    'translator',
                    'logger',
                    'mautic.helper.encryption',
                    'mautic.lead.model.lead',
                    'mautic.lead.model.company',
                    'mautic.helper.paths',
                    'mautic.core.model.notification',
                    'mautic.lead.model.field',
                    'mautic.plugin.model.integration_entity',
                    'mautic.lead.model.dnc',
                ],
            ],
            'mautic.integration.icontact' => [
                'class'     => \MauticPlugin\MauticEmailMarketingBundle\Integration\IcontactIntegration::class,
                'arguments' => [
                    'event_dispatcher',
                    'mautic.helper.cache_storage',
                    'doctrine.orm.entity_manager',
                    'session',
                    'request_stack',
                    'router',
                    'translator',
                    'logger',
                    'mautic.helper.encryption',
                    'mautic.lead.model.lead',
                    'mautic.lead.model.company',
                    'mautic.helper.paths',
                    'mautic.core.model.notification',
                    'mautic.lead.model.field',
                    'mautic.plugin.model.integration_entity',
                    'mautic.lead.model.dnc',
                ],
            ],
            'mautic.integration.mailchimp' => [
                'class'     => \MauticPlugin\MauticEmailMarketingBundle\Integration\MailchimpIntegration::class,
                'arguments' => [
                    'event_dispatcher',
                    'mautic.helper.cache_storage',
                    'doctrine.orm.entity_manager',
                    'session',
                    'request_stack',
                    'router',
                    'translator',
                    'logger',
                    'mautic.helper.encryption',
                    'mautic.lead.model.lead',
                    'mautic.lead.model.company',
                    'mautic.helper.paths',
                    'mautic.core.model.notification',
                    'mautic.lead.model.field',
                    'mautic.plugin.model.integration_entity',
                    'mautic.lead.model.dnc',
                ],
            ],
        ],
    ],
];
