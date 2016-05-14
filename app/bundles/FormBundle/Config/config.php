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
            'mautic_form_pagetoken_index' => array(
                'path'       => '/forms/pagetokens/{page}',
                'controller' => 'MauticFormBundle:SubscribedEvents\BuilderToken:index'
            ),
            'mautic_formaction_action'    => array(
                'path'       => '/forms/action/{objectAction}/{objectId}',
                'controller' => 'MauticFormBundle:Action:execute'
            ),
            'mautic_formfield_action'     => array(
                'path'       => '/forms/field/{objectAction}/{objectId}',
                'controller' => 'MauticFormBundle:Field:execute'
            ),
            'mautic_form_index'           => array(
                'path'       => '/forms/{page}',
                'controller' => 'MauticFormBundle:Form:index'
            ),
            'mautic_form_results'         => array(
                'path'       => '/forms/results/{objectId}/{page}',
                'controller' => 'MauticFormBundle:Result:index',
            ),
            'mautic_form_export'          => array(
                'path'       => '/forms/results/{objectId}/export/{format}',
                'controller' => 'MauticFormBundle:Result:export',
                'defaults'   => array(
                    'format' => 'csv'
                )
            ),
            'mautic_form_results_delete'   => array(
                'path'       => '/forms/results/{formId}/delete/{objectId}',
                'controller' => 'MauticFormBundle:Result:delete',
                'defaults'   => array(
                    'objectId' => 0
                )
            ),
            'mautic_form_action'          => array(
                'path'       => '/forms/{objectAction}/{objectId}',
                'controller' => 'MauticFormBundle:Form:execute'
            )
        ),
        'api'    => array(
            'mautic_api_getforms' => array(
                'path'       => '/forms',
                'controller' => 'MauticFormBundle:Api\FormApi:getEntities'
            ),
            'mautic_api_getform'  => array(
                'path'       => '/forms/{id}',
                'controller' => 'MauticFormBundle:Api\FormApi:getEntity'
            )
        ),
        'public' => array(
            'mautic_form_postresults'  => array(
                'path'       => '/form/submit',
                'controller' => 'MauticFormBundle:Public:submit'
            ),
            'mautic_form_generateform' => array(
                'path'       => '/form/generate.js',
                'controller' => 'MauticFormBundle:Public:generate'
            ),
            'mautic_form_postmessage'  => array(
                'path'       => '/form/message',
                'controller' => 'MauticFormBundle:Public:message'
            ),
            'mautic_form_preview'      => array(
                'path'       => '/form/{id}',
                'controller' => 'MauticFormBundle:Public:preview',
                'defaults'   => array(
                    'id' => '0'
                )
            )
        )
    ),

    'menu'     => array(
        'main' => array(
            'items'    => array(
                'mautic.form.forms' => array(
                    'route'     => 'mautic_form_index',
                    'access'    => array('form:forms:viewown', 'form:forms:viewother'),
                    'parent'    => 'mautic.core.components',
                    'priority'  => 200
                )
            )
        )
    ),

    'categories' => array(
        'form' => null
    ),

    'services' => array(
        'events' => array(
            'mautic.form.subscriber'                => array(
                'class' => 'Mautic\FormBundle\EventListener\FormSubscriber'
            ),
            'mautic.form.pagebundle.subscriber'     => array(
                'class' => 'Mautic\FormBundle\EventListener\PageSubscriber'
            ),
            'mautic.form.pointbundle.subscriber'    => array(
                'class' => 'Mautic\FormBundle\EventListener\PointSubscriber'
            ),
            'mautic.form.reportbundle.subscriber'   => array(
                'class' => 'Mautic\FormBundle\EventListener\ReportSubscriber'
            ),
            'mautic.form.campaignbundle.subscriber' => array(
                'class' => 'Mautic\FormBundle\EventListener\CampaignSubscriber'
            ),
            'mautic.form.calendarbundle.subscriber' => array(
                'class' => 'Mautic\FormBundle\EventListener\CalendarSubscriber'
            ),
            'mautic.form.leadbundle.subscriber'     => array(
                'class' => 'Mautic\FormBundle\EventListener\LeadSubscriber'
            ),
            'mautic.form.emailbundle.subscriber'    => array(
                'class' => 'Mautic\FormBundle\EventListener\EmailSubscriber'
            ),
            'mautic.form.search.subscriber'         => array(
                'class' => 'Mautic\FormBundle\EventListener\SearchSubscriber'
            ),
            'mautic.form.webhook.subscriber'        => array(
                'class' => 'Mautic\FormBundle\EventListener\WebhookSubscriber'
            ),
            'mautic.form.dashboard.subscriber'      => array(
                'class' => 'Mautic\FormBundle\EventListener\DashboardSubscriber'
            ),
        ),
        'forms'  => array(
            'mautic.form.type.form'                      => array(
                'class'     => 'Mautic\FormBundle\Form\Type\FormType',
                'arguments' => 'mautic.factory',
                'alias'     => 'mauticform'
            ),
            'mautic.form.type.field'                     => array(
                'class' => 'Mautic\FormBundle\Form\Type\FieldType',
                'alias' => 'formfield'
            ),
            'mautic.form.type.action'                    => array(
                'class' => 'Mautic\FormBundle\Form\Type\ActionType',
                'alias' => 'formaction'
            ),
            'mautic.form.type.field_propertytext'        => array(
                'class' => 'Mautic\FormBundle\Form\Type\FormFieldTextType',
                'alias' => 'formfield_text'
            ),
            'mautic.form.type.field_propertyplaceholder' => array(
                'class' => 'Mautic\FormBundle\Form\Type\FormFieldPlaceholderType',
                'alias' => 'formfield_placeholder'
            ),
            'mautic.form.type.field_propertyselect'      => array(
                'class' => 'Mautic\FormBundle\Form\Type\FormFieldSelectType',
                'alias' => 'formfield_select'
            ),
            'mautic.form.type.field_propertycaptcha'     => array(
                'class' => 'Mautic\FormBundle\Form\Type\FormFieldCaptchaType',
                'alias' => 'formfield_captcha'
            ),
            'mautic.form.type.field_propertygroup'      => array(
                'class' => 'Mautic\FormBundle\Form\Type\FormFieldGroupType',
                'alias' => 'formfield_group'
            ),
            'mautic.form.type.pointaction_formsubmit'    => array(
                'class' => 'Mautic\FormBundle\Form\Type\PointActionFormSubmitType',
                'alias' => 'pointaction_formsubmit'
            ),
            'mautic.form.type.form_list'                 => array(
                'class'     => 'Mautic\FormBundle\Form\Type\FormListType',
                'arguments' => 'mautic.factory',
                'alias'     => 'form_list'
            ),
            'mautic.form.type.campaignevent_formsubmit'  => array(
                'class' => 'Mautic\FormBundle\Form\Type\CampaignEventFormSubmitType',
                'alias' => 'campaignevent_formsubmit'
            ),
            'mautic.form.type.campaignevent_form_field_value'  => array(
                'class' => 'Mautic\FormBundle\Form\Type\CampaignEventFormFieldValueType',
                'arguments' => 'mautic.factory',
                'alias' => 'campaignevent_form_field_value'
            ),
            'mautic.form.type.form_submitaction_sendemail'  => array(
                'class'     => 'Mautic\FormBundle\Form\Type\SubmitActionEmailType',
                'arguments' => 'mautic.factory',
                'alias'     => 'form_submitaction_sendemail'
            )
        )
    )
);
