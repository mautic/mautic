<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\NotificationBundle\EventListener;

use Mautic\CoreBundle\Event\TokenReplacementEvent;
use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\FormBundle\Event\FormBuilderEvent;
use Mautic\FormBundle\Event\SubmissionEvent;
use Mautic\FormBundle\FormEvents;
use Mautic\LeadBundle\Entity\DoNotContact;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\NotificationBundle\Api\AbstractNotificationApi;
use Mautic\NotificationBundle\Event\NotificationSendEvent;
use Mautic\NotificationBundle\Model\NotificationModel;
use Mautic\NotificationBundle\NotificationEvents;
use Mautic\PluginBundle\Helper\IntegrationHelper;

/**
 * Class FormSubscriber.
 */
class FormSubscriber extends CommonSubscriber
{
    /**
     * @var LeadModel
     */
    protected $leadModel;

    /**
     * @var NotificationModel
     */
    protected $notificationModel;

    /**
     * @var AbstractNotificationApi
     */
    protected $notificationApi;

    /**
     * @var IntegrationHelper
     */
    protected $integrationHelper;

    /**
     * CampaignSubscriber constructor.
     *
     * @param IntegrationHelper       $integrationHelper
     * @param LeadModel               $leadModel
     * @param NotificationModel       $notificationModel
     * @param AbstractNotificationApi $notificationApi
     */
    public function __construct(
        IntegrationHelper $integrationHelper,
        LeadModel $leadModel,
        NotificationModel $notificationModel,
        AbstractNotificationApi $notificationApi
    ) {
        $this->integrationHelper = $integrationHelper;
        $this->leadModel         = $leadModel;
        $this->notificationModel = $notificationModel;
        $this->notificationApi   = $notificationApi;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            FormEvents::FORM_ON_BUILD                            => ['onFormBuild', 0],
            NotificationEvents::NOTIFICATION_ON_FORM_ACTION_SEND => ['onFormActionSend', 0],
        ];
    }

    /**
     * @param FormBuilderEvent $event
     */
    public function onFormBuild(FormBuilderEvent $event)
    {
        $action = [
            'group'       => 'mautic.notification.actions',
            'description' => 'mautic.notification.actions.mobile_tooltip',
            'label'       => 'mautic.notification.actions.send_mobile_notification',
            'formType'    => 'notification_list',
            'formTheme'   => 'MauticNotificationBundle:FormTheme\NotificationSendList',
            'eventName'   => NotificationEvents::NOTIFICATION_ON_FORM_ACTION_SEND,
        ];
        $event->addSubmitAction('notification.send_mobile_notification', $action);
    }

    public function onFormActionSend(SubmissionEvent $event)
    {
        $lead         = $event->getLead();
        $actionConfig = $event->getActionConfig();

        if ($this->leadModel->isContactable($lead, 'notification') !== DoNotContact::IS_CONTACTABLE) {
            return $event->setFailed('mautic.notification.campaign.failed.not_contactable');
        }

        // If lead has subscribed on multiple devices, get all of them.
        /** @var \Mautic\NotificationBundle\Entity\PushID[] $pushIDs */
        $pushIDs = $lead->getPushIDs();

        $playerID = [];

        foreach ($pushIDs as $pushID) {
            // Skip non-mobile PushIDs if this is a mobile event
            if ($event->checkContext('notification.send_mobile_notification') && $pushID->isMobile() == false) {
                continue;
            }

            // Skip mobile PushIDs if this is a non-mobile event
            if ($event->checkContext('notification.send_notification') && $pushID->isMobile() == true) {
                continue;
            }

            $playerID[] = $pushID->getPushID();
        }

        if (empty($playerID)) {
            return $event->setFailed('mautic.notification.campaign.failed.not_subscribed');
        }

        $notificationId = (int) $actionConfig['properties'][0];

        /** @var \Mautic\NotificationBundle\Entity\Notification $notification */
        $notification = $this->notificationModel->getEntity($notificationId);

        if ($notification->getId() !== $notificationId) {
            return $event->setFailed('mautic.notification.campaign.failed.missing_entity');
        }

        if ($url = $notification->getUrl()) {
            $url = $this->notificationApi->convertToTrackedUrl(
                $url,
                [
                    'notification' => $notification->getId(),
                    'lead'         => $lead->getId(),
                ],
                $notification
            );
        }

        /** @var TokenReplacementEvent $tokenEvent */
        $tokenEvent = $this->dispatcher->dispatch(
            NotificationEvents::TOKEN_REPLACEMENT,
            new TokenReplacementEvent(
                $notification->getMessage(),
                $lead,
                ['channel' => ['notification', $notification->getId()]]
            )
        );

        /** @var NotificationSendEvent $sendEvent */
        $sendEvent = $this->dispatcher->dispatch(
            NotificationEvents::NOTIFICATION_ON_SEND,
            new NotificationSendEvent($tokenEvent->getContent(), $notification->getHeading(), $lead)
        );

        $notification->setUrl($url);
        $notification->setMessage($sendEvent->getMessage());
        $notification->setHeading($sendEvent->getHeading());

        $response = $this->notificationApi->sendNotification(
            $playerID,
            $notification
        );

        $event->setChannel('notification', $notification->getId());

        // If for some reason the call failed, tell mautic to try again by return false
        if ($response->code !== 200) {
            return $event->setResult(false);
        }

        $this->notificationModel->createStatEntry($notification, $lead);
        $this->notificationModel->getRepository()->upCount($notificationId);

        $result = [
            'status'  => 'mautic.notification.timeline.status.delivered',
            'type'    => 'mautic.notification.notification',
            'id'      => $notification->getId(),
            'name'    => $notification->getName(),
            'heading' => $sendEvent->getHeading(),
            'content' => $sendEvent->getMessage(),
        ];

        $event->setResult($result);
    }
}
