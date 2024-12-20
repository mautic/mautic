<?php

namespace Mautic\NotificationBundle\EventListener;

use Mautic\CampaignBundle\CampaignEvents;
use Mautic\CampaignBundle\Entity\LeadEventLog;
use Mautic\CampaignBundle\Event\CampaignBuilderEvent;
use Mautic\CampaignBundle\Event\PendingEvent;
use Mautic\CoreBundle\Event\TokenReplacementEvent;
use Mautic\LeadBundle\Entity\DoNotContact;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Model\DoNotContact as DoNotContactModel;
use Mautic\NotificationBundle\Api\AbstractNotificationApi;
use Mautic\NotificationBundle\Entity\Notification;
use Mautic\NotificationBundle\Event\NotificationSendEvent;
use Mautic\NotificationBundle\Form\Type\MobileNotificationSendType;
use Mautic\NotificationBundle\Form\Type\NotificationSendType;
use Mautic\NotificationBundle\Model\NotificationModel;
use Mautic\NotificationBundle\NotificationEvents;
use Mautic\PluginBundle\Helper\IntegrationHelper;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class CampaignSubscriber implements EventSubscriberInterface
{
    /**
     * @var string
     */
    public const EVENT_ACTION_SEND_MOBILE_NOTIFICATION = 'notification.send_mobile_notification';

    /**
     * @var string
     */
    public const EVENT_ACTION_SEND_NOTIFICATION = 'notification.send_notification';

    /**
     * The maximum number of `include_player_ids` that can be sent within a single request.
     *
     * @var int
     */
    protected const MAX_PLAYER_IDS_PER_REQUEST = 2000;

    public function __construct(
        private IntegrationHelper $integrationHelper,
        private NotificationModel $notificationModel,
        private AbstractNotificationApi $notificationApi,
        private EventDispatcherInterface $dispatcher,
        private DoNotContactModel $doNotContact,
        private TranslatorInterface $translator,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CampaignEvents::CAMPAIGN_ON_BUILD            => ['onCampaignBuild', 0],
            NotificationEvents::ON_CAMPAIGN_BATCH_ACTION => ['onCampaignBatchAction', 0],
        ];
    }

    public function onCampaignBuild(CampaignBuilderEvent $event): void
    {
        $integration = $this->integrationHelper->getIntegrationObject('OneSignal');

        if (!$integration || false === $integration->getIntegrationSettings()->getIsPublished()) {
            return;
        }

        $features = $integration->getSupportedFeatures();

        if (in_array('mobile', $features)) {
            $event->addAction(
                static::EVENT_ACTION_SEND_MOBILE_NOTIFICATION,
                [
                    'label'            => 'mautic.notification.campaign.send_mobile_notification',
                    'description'      => 'mautic.notification.campaign.send_mobile_notification.tooltip',
                    'batchEventName'   => NotificationEvents::ON_CAMPAIGN_BATCH_ACTION,
                    'formType'         => MobileNotificationSendType::class,
                    'formTypeOptions'  => ['update_select' => 'campaignevent_properties_notification'],
                    'formTheme'        => '@MauticNotification/FormTheme/NotificationSendList/_notificationsend_list_row.html.twig',
                    'timelineTemplate' => '@MauticNotification/SubscribedEvents/Timeline/index.html.twig',
                    'channel'          => 'mobile_notification',
                    'channelIdField'   => 'mobile_notification',
                ]
            );
        }

        $event->addAction(
            static::EVENT_ACTION_SEND_NOTIFICATION,
            [
                'label'            => 'mautic.notification.campaign.send_notification',
                'description'      => 'mautic.notification.campaign.send_notification.tooltip',
                'batchEventName'   => NotificationEvents::ON_CAMPAIGN_BATCH_ACTION,
                'formType'         => NotificationSendType::class,
                'formTypeOptions'  => ['update_select' => 'campaignevent_properties_notification'],
                'formTheme'        => '@MauticNotification/FormTheme/NotificationSendList/_notificationsend_list_row.html.twig',
                'timelineTemplate' => '@MauticNotification/SubscribedEvents/Timeline/index.html.twig',
                'channel'          => 'notification',
                'channelIdField'   => 'notification',
            ]
        );
    }

    public function onCampaignBatchAction(PendingEvent $event): void
    {
        if (!$event->checkContext(static::EVENT_ACTION_SEND_NOTIFICATION) && !$event->checkContext(static::EVENT_ACTION_SEND_MOBILE_NOTIFICATION)) {
            return;
        }

        $notificationId = $event->getEvent()->getProperties()['notification'] ?? null;
        $notification   = $notificationId ? $this->notificationModel->getEntity((int) $notificationId) : null;

        if (!$notification) {
            $event->passAllWithError($this->translator->trans('mautic.notification.campaign.failed.missing_entity'));

            return;
        }

        if (!$notification->getIsPublished()) {
            $event->passAllWithError($this->translator->trans('mautic.notification.campaign.failed.unpublished'));

            return;
        }

        $event->setChannel('notification', $notification->getId());

        if ($notification->getUrl()) {
            $this->sendNotificationPerLead($notification, $event);
        } else {
            $this->sendNotificationsInBatches($notification, $event);
        }
    }

    private function sendNotificationPerLead(Notification $notification, PendingEvent $event): void
    {
        foreach ($event->getPending() as $log) {
            if (!$this->isLeadContactable($event, $log)) {
                continue;
            }

            $playerIds = $this->getLeadPlayerIds($event, $log);

            if (!$playerIds) {
                continue;
            }

            $sendNotification = $this->buildNotificationToSend($notification, $log->getLead());
            $response         = $this->notificationApi->sendNotification($playerIds, $sendNotification);
            $this->processResponse($response, $event, $log, $notification, $sendNotification);
        }
    }

    private function sendNotificationsInBatches(Notification $notification, PendingEvent $event): void
    {
        $batches       = $this->buildBatches($event, $notification);
        $processedLogs = [];

        foreach ($batches as $batch) {
            $sendNotification = $batch['sendNotification'];
            $playerIdsChunks  = array_chunk($batch['playerIds'], static::MAX_PLAYER_IDS_PER_REQUEST, true);

            foreach ($playerIdsChunks as $playerIdsChunk) {
                $playerIds = array_keys($playerIdsChunk);
                $response  = $this->notificationApi->sendNotification($playerIds, $sendNotification);

                foreach ($playerIdsChunk as $log) {
                    if (!isset($processedLogs[$log->getId()])) {
                        $processedLogs[$log->getId()] = $log;
                        $this->processResponse($response, $event, $log, $notification, $sendNotification);
                    }
                }
            }
        }
    }

    private function isLeadContactable(PendingEvent $event, LeadEventLog $log): bool
    {
        $contactable = DoNotContact::IS_CONTACTABLE === $this->doNotContact->isContactable($log->getLead(), 'notification');

        if (!$contactable) {
            $event->passWithError($log, $this->translator->trans('mautic.notification.campaign.failed.not_contactable'));
        }

        return $contactable;
    }

    /**
     * @return string[]
     */
    private function getLeadPlayerIds(PendingEvent $event, LeadEventLog $log): array
    {
        $playerIds = [];

        foreach ($log->getLead()->getPushIDs() as $pushID) {
            // Skip non-mobile PushIDs if this is a mobile event
            if ($event->checkContext(static::EVENT_ACTION_SEND_MOBILE_NOTIFICATION) && !$pushID->isMobile()) {
                continue;
            }

            // Skip mobile PushIDs if this is a non-mobile event
            if ($event->checkContext(static::EVENT_ACTION_SEND_NOTIFICATION) && $pushID->isMobile()) {
                continue;
            }

            $playerIds[] = $pushID->getPushID();
        }

        if (!$playerIds) {
            $event->passWithError($log, $this->translator->trans('mautic.notification.campaign.failed.not_subscribed'));
        }

        return $playerIds;
    }

    private function buildNotificationToSend(Notification $notification, Lead $lead): Notification
    {
        /** @var TokenReplacementEvent $tokenEvent */
        $tokenEvent = $this->dispatcher->dispatch(
            new TokenReplacementEvent(
                $notification->getMessage(),
                $lead,
                ['channel' => ['notification', $notification->getId()]]
            ),
            NotificationEvents::TOKEN_REPLACEMENT
        );

        /** @var NotificationSendEvent $sendEvent */
        $sendEvent = $this->dispatcher->dispatch(
            new NotificationSendEvent($tokenEvent->getContent(), $notification->getHeading(), $lead),
            NotificationEvents::NOTIFICATION_ON_SEND
        );

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

        // prevent rewrite notification entity
        $sendNotification = clone $notification;
        $sendNotification->setUrl($url);
        $sendNotification->setMessage($sendEvent->getMessage());
        $sendNotification->setHeading($sendEvent->getHeading());

        return $sendNotification;
    }

    private function processResponse(ResponseInterface $response, PendingEvent $event, LeadEventLog $log, Notification $notification, Notification $sendNotification): void
    {
        // if for some reason the call failed, tell mautic to try again
        if (200 !== $response->getStatusCode()) {
            $event->fail($log, sprintf('%s (%s)', (string) $response->getBody(), $response->getStatusCode()));

            return;
        }

        $this->notificationModel->createStatEntry($notification, $log->getLead(), 'campaign.event', $event->getEvent()->getId());
        $this->notificationModel->getRepository()->upCount($notification->getId());

        $result = [
            'status'  => 'mautic.notification.timeline.status.delivered',
            'type'    => 'mautic.notification.notification',
            'id'      => $notification->getId(),
            'name'    => $notification->getName(),
            'heading' => $sendNotification->getHeading(),
            'content' => $sendNotification->getMessage(),
        ];
        $log->appendToMetadata($result);
        $event->pass($log);
    }

    /**
     * @return array<string,mixed[]>
     */
    private function buildBatches(PendingEvent $event, Notification $notification): array
    {
        $batches = [];

        foreach ($event->getPending() as $log) {
            if (!$this->isLeadContactable($event, $log)) {
                continue;
            }

            $playerIds = $this->getLeadPlayerIds($event, $log);

            if (!$playerIds) {
                continue;
            }

            $sendNotification = $this->buildNotificationToSend($notification, $log->getLead());
            $uniqueKey        = md5(sprintf('[%s][%s]', $sendNotification->getHeading(), $sendNotification->getMessage()));

            if (!isset($batches[$uniqueKey])) {
                $batches[$uniqueKey] = [
                    'sendNotification' => $sendNotification,
                    'playerIds'        => [],
                ];
            }

            foreach ($playerIds as $playerId) {
                $batches[$uniqueKey]['playerIds'][$playerId] = $log;
            }
        }

        return $batches;
    }
}
