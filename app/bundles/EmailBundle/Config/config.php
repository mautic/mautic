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
            'mautic_email_index'  => array(
                'path'       => '/emails/{page}',
                'controller' => 'MauticEmailBundle:Email:index'
            ),
            'mautic_email_action' => array(
                'path'       => '/emails/{objectAction}/{objectId}',
                'controller' => 'MauticEmailBundle:Email:execute'
            )
        ),
        'api'    => array(
            'mautic_api_getemails' => array(
                'path'       => '/emails',
                'controller' => 'MauticEmailBundle:Api\EmailApi:getEntities'
            ),
            'mautic_api_getemail'  => array(
                'path'       => '/emails/{id}',
                'controller' => 'MauticEmailBundle:Api\EmailApi:getEntity'
            ),
            'mautic_api_sendleademail'  => array(
                'path'       => '/emails/{id}/send/lead/{leadId}',
                'controller' => 'MauticEmailBundle:Api\EmailApi:sendLead',
                'method'     => 'POST'
            ),
            'mautic_api_sendemail'  => array(
                'path'       => '/emails/{id}/send',
                'controller' => 'MauticEmailBundle:Api\EmailApi:send',
                'method'     => 'POST'
            )
        ),
        'public' => array(
            'mautic_email_tracker'     => array(
                'path'       => '/email/{idHash}.gif',
                'controller' => 'MauticEmailBundle:Public:trackingImage'
            ),
            'mautic_email_webview'     => array(
                'path'       => '/email/view/{idHash}',
                'controller' => 'MauticEmailBundle:Public:index'
            ),
            'mautic_email_unsubscribe' => array(
                'path'       => '/email/unsubscribe/{idHash}',
                'controller' => 'MauticEmailBundle:Public:unsubscribe'
            ),
            'mautic_email_resubscribe' => array(
                'path'       => '/email/resubscribe/{idHash}',
                'controller' => 'MauticEmailBundle:Public:resubscribe'
            ),
            'mautic_mailer_transport_callback' => array(
                'path'         => '/mailer/{transport}/callback',
                'controller'   => 'MauticEmailBundle:Public:mailerCallback'
            ),
            'mautic_email_preview' => array(
                'path'       => '/email/preview/{objectId}',
                'controller' => 'MauticEmailBundle:Public:preview'
            )
        )
    ),

    'menu'     => array(
        'main' => array(
            'priority' => 15,
            'items'    => array(
                'mautic.email.emails' => array(
                    'id'        => 'mautic_email_root',
                    'iconClass' => 'fa-send',
                    'access'    => array('email:emails:viewown', 'email:emails:viewother'),
                    'children'  => array(
                        'mautic.email.menu.index'     => array(
                            'route' => 'mautic_email_index'
                        ),
                        'mautic.category.menu.index' => array(
                            'bundle' => 'email'
                        )
                    )
                )
            )
        )
    ),

    'services' => array(
        'events' => array(
            'mautic.email.subscriber'                => array(
                'class' => 'Mautic\EmailBundle\EventListener\EmailSubscriber'
            ),
            'mautic.emailbuilder.subscriber'         => array(
                'class' => 'Mautic\EmailBundle\EventListener\BuilderSubscriber'
            ),
            'mautic.email.campaignbundle.subscriber' => array(
                'class' => 'Mautic\EmailBundle\EventListener\CampaignSubscriber'
            ),
            'mautic.email.formbundle.subscriber'     => array(
                'class' => 'Mautic\EmailBundle\EventListener\FormSubscriber'
            ),
            'mautic.email.reportbundle.subscriber'   => array(
                'class' => 'Mautic\EmailBundle\EventListener\ReportSubscriber'
            ),
            'mautic.email.leadbundle.subscriber'     => array(
                'class' => 'Mautic\EmailBundle\EventListener\LeadSubscriber'
            ),
            'mautic.email.pointbundle.subscriber'    => array(
                'class' => 'Mautic\EmailBundle\EventListener\PointSubscriber'
            ),
            'mautic.email.calendarbundle.subscriber' => array(
                'class' => 'Mautic\EmailBundle\EventListener\CalendarSubscriber'
            ),
            'mautic.email.search.subscriber'         => array(
                'class' => 'Mautic\EmailBundle\EventListener\SearchSubscriber'
            )
        ),
        'forms'  => array(
            'mautic.form.type.email'                      => array(
                'class'     => 'Mautic\EmailBundle\Form\Type\EmailType',
                'arguments' => 'mautic.factory',
                'alias'     => 'emailform'
            ),
            'mautic.form.type.emailvariant'               => array(
                'class'     => 'Mautic\EmailBundle\Form\Type\VariantType',
                'arguments' => 'mautic.factory',
                'alias'     => 'emailvariant'
            ),
            'mautic.form.type.email_list'                 => array(
                'class'     => 'Mautic\EmailBundle\Form\Type\EmailListType',
                'arguments' => 'mautic.factory',
                'alias'     => 'email_list'
            ),
            'mautic.form.type.emailopen_list'             => array(
                'class' => 'Mautic\EmailBundle\Form\Type\EmailOpenType',
                'alias' => 'emailopen_list'
            ),
            'mautic.form.type.emailsend_list'             => array(
                'class'     => 'Mautic\EmailBundle\Form\Type\EmailSendType',
                'arguments' => 'mautic.factory',
                'alias'     => 'emailsend_list'
            ),
            'mautic.form.type.formsubmit_sendemail_admin' => array(
                'class' => 'Mautic\EmailBundle\Form\Type\FormSubmitActionUserEmailType',
                'alias' => 'email_submitaction_useremail'
            ),
            'mautic.email.type.email_abtest_settings'     => array(
                'class' => 'Mautic\EmailBundle\Form\Type\AbTestPropertiesType',
                'alias' => 'email_abtest_settings'
            ),
            'mautic.email.type.batch_send'     => array(
                'class' => 'Mautic\EmailBundle\Form\Type\BatchSendType',
                'alias' => 'batch_send'
            )
        ),
        'other'  => array(
            'mautic.validator.leadlistaccess' => array(
                'class'     => 'Mautic\LeadBundle\Form\Validator\Constraints\LeadListAccessValidator',
                'arguments' => 'mautic.factory',
                'tag'       => 'validator.constraint_validator',
                'alias'     => 'leadlist_access'
            )
        )
    )
);