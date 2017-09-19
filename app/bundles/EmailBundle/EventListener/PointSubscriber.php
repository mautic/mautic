<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\EventListener;

use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\EmailBundle\EmailEvents;
use Mautic\EmailBundle\Event\EmailOpenEvent;
use Mautic\EmailBundle\Event\EmailSendEvent;
use Mautic\EmailBundle\Form\Type\EmailToUserType;
use Mautic\PointBundle\Event\PointBuilderEvent;
use Mautic\PointBundle\Event\TriggerBuilderEvent;
use Mautic\PointBundle\Model\PointModel;
use Mautic\PointBundle\PointEvents;

/**
 * Class PointSubscriber.
 */
class PointSubscriber extends CommonSubscriber
{
    /**
     * @var PointModel
     */
    protected $pointModel;

    /**
     * PointSubscriber constructor.
     *
     * @param PointModel $pointModel
     */
    public function __construct(PointModel $pointModel)
    {
        $this->pointModel = $pointModel;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            PointEvents::POINT_ON_BUILD   => ['onPointBuild', 0],
            PointEvents::TRIGGER_ON_BUILD => ['onTriggerBuild', 0],
            EmailEvents::EMAIL_ON_OPEN    => ['onEmailOpen', 0],
            EmailEvents::EMAIL_ON_SEND    => ['onEmailSend', 0],
        ];
    }

    /**
     * @param PointBuilderEvent $event
     */
    public function onPointBuild(PointBuilderEvent $event)
    {
        $action = [
            'group'    => 'mautic.email.actions',
            'label'    => 'mautic.email.point.action.open',
            'callback' => ['\\Mautic\\EmailBundle\\Helper\\PointEventHelper', 'validateEmail'],
            'formType' => 'emailopen_list',
        ];

        $event->addAction('email.open', $action);

        $action = [
            'group'    => 'mautic.email.actions',
            'label'    => 'mautic.email.point.action.send',
            'callback' => ['\\Mautic\\EmailBundle\\Helper\\PointEventHelper', 'validateEmail'],
            'formType' => 'emailopen_list',
        ];

        $event->addAction('email.send', $action);
    }

    /**
     * @param TriggerBuilderEvent $event
     */
    public function onTriggerBuild(TriggerBuilderEvent $event)
    {
        $sendEvent = [
            'group'           => 'mautic.email.point.trigger',
            'label'           => 'mautic.email.point.trigger.sendemail',
            'callback'        => ['\\Mautic\\EmailBundle\\Helper\\PointEventHelper', 'sendEmail'],
            'formType'        => 'emailsend_list',
            'formTypeOptions' => ['update_select' => 'pointtriggerevent_properties_email'],
            'formTheme'       => 'MauticEmailBundle:FormTheme\EmailSendList',
        ];

        $event->addEvent('email.send', $sendEvent);

        $sendToOwnerEvent = [
          'group'           => 'mautic.email.point.trigger',
          'label'           => 'mautic.email.point.trigger.send_email_to_user',
          'formType'        => EmailToUserType::class,
          'formTypeOptions' => ['update_select' => 'pointtriggerevent_properties_email'],
          'formTheme'       => 'MauticEmailBundle:FormTheme\EmailSendList',
        ];

        $event->addEvent('email.send_to_user', $sendToOwnerEvent);
    }

    /**
     * Trigger point actions for email open.
     *
     * @param EmailOpenEvent $event
     */
    public function onEmailOpen(EmailOpenEvent $event)
    {
        $this->pointModel->triggerAction('email.open', $event->getEmail());
    }

    /**
     * Trigger point actions for email send.
     *
     * @param EmailSendEvent $event
     */
    public function onEmailSend(EmailSendEvent $event)
    {
        if ($leadArray = $event->getLead()) {
            $lead = $this->em->getReference('MauticLeadBundle:Lead', $leadArray['id']);
        } else {
            return;
        }

        $this->pointModel->triggerAction('email.send', $event->getEmail(), null, $lead);
    }
}
