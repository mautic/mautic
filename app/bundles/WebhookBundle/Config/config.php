<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

return array(
    'routes'   => array(
        'main'   => array(
            'mautic_webhook_index'           => array(
                'path'       => '/webhooks/{page}',
                'controller' => 'MauticWebhookBundle:Webhook:index'
            ),
            'mautic_webhook_action'             => array(
                'path'       => '/webhooks/{objectAction}/{objectId}',
                'controller' => 'MauticWebhookBundle:Webhook:execute'
            ),
        ),
    ),

    'menu'     => array(
        'main' => array(
            'priority' => 50,
            'items'    => array(
                'mautic.webhook.webhooks' => array(
                    'id'        => 'mautic_webhook_root',
                    'iconClass' => 'fa-exchange',
                    'access'    => array('webhook:webhooks:viewown', 'webhook:webhooks:viewother'),
                    'children'  => array(
                        'mautic.webhook.webhook.menu.index' => array(
                            'route' => 'mautic_webhook_index'
                        ),
                        'mautic.category.menu.index'  => array(
                            'bundle' => 'webhook'
                        )
                    )
                )
            )
        )
    ),

    'services' => array(
        'forms'  => array(
            'mautic.form.type.webhook'                      => array(
                'class'     => 'Mautic\WebhookBundle\Form\Type\WebhookType',
                'arguments' => 'mautic.factory',
                'alias'     => 'webhook'
            ),
            'mautic.form.type.webhookconfig'  => array(
                'class' => 'Mautic\WebhookBundle\Form\Type\ConfigType',
                'alias' => 'webhookconfig'
            )
        ),
        'events' => array(
            'mautic.webhook.lead.subscriber'                => array(
                'class' => 'Mautic\WebhookBundle\EventListener\LeadSubscriber'
            ),
            'mautic.webhook.form.subscriber'                => array(
                'class' => 'Mautic\WebhookBundle\EventListener\FormSubscriber'
            ),
            'mautic.webhook.email.subscriber'                => array(
                'class' => 'Mautic\WebhookBundle\EventListener\EmailSubscriber'
            ),
            'mautic.webhook.page.hit.subscriber'                => array(
                'class' => 'Mautic\WebhookBundle\EventListener\PageSubscriber'
            ),
            'mautic.webhook.config.subscriber' => array(
                'class' => 'Mautic\WebhookBundle\EventListener\ConfigSubscriber'
            ),
        )
    ),

    'parameters' => array()
);