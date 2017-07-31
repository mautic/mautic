<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticCrmBundle\EventListener;

use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\FormBundle\Event\FormBuilderEvent;
use Mautic\FormBundle\FormEvents;
use Mautic\PointBundle\Event\TriggerBuilderEvent;
use Mautic\PointBundle\PointEvents;


/**
 * Class PipedriveActionSubscriber.
 * inspired from EmailBundle/FormSubscriber
 */
class PipedriveActionSubscriber extends CommonSubscriber
{
    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            FormEvents::FORM_ON_BUILD => ['onFormBuilder', 0],
            PointEvents::POINT_ON_BUILD   => ['onPointBuild', 0],
            //PointEvents::TRIGGER_ON_BUILD => ['onTriggerBuild', 0],
        ];
    }

    /**
     * Add a send email actions to available form submit actions.
     *
     * @param FormBuilderEvent $event
     */
    public function onFormBuilder(FormBuilderEvent $event)
    {
        // Add form submit actions
        $action = [
            'group'             => 'mautic.pipedrive.actions',
            'label'             => 'mautic.pipedrive.actions.push_offer',
            'description'       => 'mautic.pipedrive.actions.tooltip',
            'formType'          => 'pipedrive_offer_action',
            'callback'          => ['\\MauticPlugin\\MauticCrmBundle\\Helper\\EventHelper', 'pushOffer'],
            'allowCampaignForm' => true,
        ];

        $event->addSubmitAction('pipedrive.push_offer', $action);
    }

    // /**
    //  * @param PointBuilderEvent $event
    //  */
    // public function onPointBuild(PointBuilderEvent $event)
    // {
    //     $action = [
    //         'group'    => 'mautic.email.actions',
    //         'label'    => 'mautic.email.point.action.open',
    //         'callback' => ['\\Mautic\\EmailBundle\\Helper\\PointEventHelper', 'validateEmail'],
    //         'formType' => 'emailopen_list',
    //     ];

    //     $event->addAction('email.open', $action);

    //     $action = [
    //         'group'    => 'mautic.email.actions',
    //         'label'    => 'mautic.email.point.action.send',
    //         'callback' => ['\\Mautic\\EmailBundle\\Helper\\PointEventHelper', 'validateEmail'],
    //         'formType' => 'emailopen_list',
    //     ];

    //     $event->addAction('email.send', $action);
    // }


    // /**
    //  * @param TriggerBuilderEvent $event
    //  */
    // public function onTriggerBuild(TriggerBuilderEvent $event)
    // {
    //     $sendEvent = [
    //         'group'             => 'mautic.pipedrive.actions',
    //         'label'             => 'mautic.pipedrive.actions.push_offer',
    //         'description'       => 'mautic.pipedrive.actions.tooltip',
    //         // 'callback'        => ['\\Mautic\\EmailBundle\\Helper\\PointEventHelper', 'sendEmail'],
    //         'callback'          => ['\\MauticPlugin\\MauticCrmBundle\\Helper\\EventHelper', 'pushOffer'],

    //         'formType'        => 'pipedrive_offer_action',
    //         //'formTypeOptions' => ['update_select' => 'pointtriggerevent_properties_email'],
    //         //'formTheme'       => 'MauticEmailBundle:FormTheme\EmailSendList',
    //     ];

    //     $event->addEvent('email.send', $sendEvent);
    // }
}
