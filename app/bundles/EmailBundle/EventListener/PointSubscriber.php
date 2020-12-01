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

use Doctrine\ORM\EntityManager;
use Mautic\EmailBundle\EmailEvents;
use Mautic\EmailBundle\Event\EmailOpenEvent;
use Mautic\EmailBundle\Event\EmailSendEvent;
use Mautic\EmailBundle\Form\Type\EmailSendType;
use Mautic\EmailBundle\Form\Type\EmailToUserType;
use Mautic\EmailBundle\Form\Type\PointActionEmailOpenType;
use Mautic\EmailBundle\Form\Type\PointActionEmailSendType;
use Mautic\EmailBundle\Helper\PointEventHelper;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\PointBundle\Event\PointBuilderEvent;
use Mautic\PointBundle\Event\PointChangeActionExecutedEvent;
use Mautic\PointBundle\Event\TriggerBuilderEvent;
use Mautic\PointBundle\Model\PointModel;
use Mautic\PointBundle\PointEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class PointSubscriber implements EventSubscriberInterface
{
    private $pointModel;

    private $entityManager;

    private $triggered = [];

    public function __construct(PointModel $pointModel, EntityManager $entityManager)
    {
        $this->pointModel    = $pointModel;
        $this->entityManager = $entityManager;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            PointEvents::POINT_ON_BUILD                     => ['onPointBuild', 0],
            PointEvents::TRIGGER_ON_BUILD                   => ['onTriggerBuild', 0],
            EmailEvents::EMAIL_ON_OPEN                      => ['onEmailOpen', 0],
            EmailEvents::EMAIL_ON_SEND                      => ['onEmailSend', 0],
            EmailEvents::ON_POINT_CHANGE_ACTION_EXECUTED    => [
                ['onEmailOpenPointChange', 0],
                ['onEmailSentPointChange', 1],
            ],
        ];
    }

    public function onPointBuild(PointBuilderEvent $event)
    {
        $action = [
            'group'     => 'mautic.email.actions',
            'label'     => 'mautic.email.point.action.open',
            'eventName' => EmailEvents::ON_POINT_CHANGE_ACTION_EXECUTED,
            'formType'  => PointActionEmailOpenType::class,
        ];

        $event->addAction('email.open', $action);

        $action = [
            'group'     => 'mautic.email.actions',
            'label'     => 'mautic.email.point.action.send',
            'eventName' => EmailEvents::ON_POINT_CHANGE_ACTION_EXECUTED,
            'formType'  => PointActionEmailSendType::class,
        ];

        $event->addAction('email.send', $action);
    }

    public function onTriggerBuild(TriggerBuilderEvent $event)
    {
        $sendEvent = [
            'group'           => 'mautic.email.point.trigger',
            'label'           => 'mautic.email.point.trigger.sendemail',
            'callback'        => ['\\Mautic\\EmailBundle\\Helper\\PointEventHelper', 'sendEmail'],
            'formType'        => EmailSendType::class,
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
          'eventName'       => EmailEvents::ON_SENT_EMAIL_TO_USER,
        ];

        $event->addEvent('email.send_to_user', $sendToOwnerEvent);
    }

    /**
     * Trigger point actions for email open.
     */
    public function onEmailOpen(EmailOpenEvent $event)
    {
        $this->pointModel->triggerAction('email.open', $event->getEmail());
    }

    /**
     * Trigger point actions for email send.
     */
    public function onEmailSend(EmailSendEvent $event)
    {
        $leadArray = $event->getLead();
        if ($leadArray && is_array($leadArray) && !empty($leadArray['id'])) {
            $lead = $this->entityManager->getReference(Lead::class, $leadArray['id']);
        } else {
            return;
        }

        if ($this->shouldTriggerPointEmailSendAction($event, $lead)) {
            $this->pointModel->triggerAction('email.send', $event->getEmail(), null, $lead, true);
        }
    }

    private function shouldTriggerPointEmailSendAction(EmailSendEvent $event, Lead $lead)
    {
        if ($event->getEmail()) {
            if (!isset($this->triggered[$lead->getId()][$event->getEmail()->getId()])) {
                $this->triggered[$lead->getId()][$event->getEmail()->getId()] = true;

                return true;
            }
        }

        return false;
    }

    public function onEmailOpenPointChange(PointChangeActionExecutedEvent $changeActionExecutedEvent)
    {
        $action = $changeActionExecutedEvent->getPointAction();

        if ('email.open' != $action->getType()) {
            return;
        }
        /** @var Email $eventDetails */
        $eventDetails = $changeActionExecutedEvent->getEventDetails();

        if (!PointEventHelper::validateEmail($eventDetails, $action->convertToArray())) {
            $changeActionExecutedEvent->setFailed();

            return;
        }
        $triggerMode = isset($action->getProperties()['triggerMode']) ? $action->getProperties()['triggerMode'] : null;
        if ('internalId' == $triggerMode) {
            $changeActionExecutedEvent->setStatusFromLogsForInternalId($eventDetails->getId());

            return;
        }

        $changeActionExecutedEvent->setStatusFromLogs();
    }

    public function onEmailSentPointChange(PointChangeActionExecutedEvent $changeActionExecutedEvent)
    {
        $action = $changeActionExecutedEvent->getPointAction();

        if ('email.send' != $action->getType()) {
            return;
        }

        /** @var Email $eventDetails */
        $eventDetails = $changeActionExecutedEvent->getEventDetails();

        if (!PointEventHelper::validateEmail($eventDetails, $action->convertToArray())) {
            $changeActionExecutedEvent->setFailed();

            return;
        }

        $changeActionExecutedEvent->setStatusFromLogs();
    }
}
