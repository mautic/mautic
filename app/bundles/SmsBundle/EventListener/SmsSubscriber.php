<?php

namespace Mautic\SmsBundle\EventListener;

use Mautic\AssetBundle\Helper\TokenHelper as AssetTokenHelper;
use Mautic\CoreBundle\Event\TokenReplacementEvent;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Model\AuditLogModel;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Helper\TokenHelper;
use Mautic\PageBundle\Entity\Trackable;
use Mautic\PageBundle\Helper\TokenHelper as PageTokenHelper;
use Mautic\PageBundle\Model\TrackableModel;
use Mautic\SmsBundle\Event\SmsEvent;
use Mautic\SmsBundle\Helper\SmsHelper;
use Mautic\SmsBundle\SmsEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class SmsSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private AuditLogModel $auditLogModel,
        private TrackableModel $trackableModel,
        private PageTokenHelper $pageTokenHelper,
        private AssetTokenHelper $assetTokenHelper,
        private SmsHelper $smsHelper,
        private CoreParametersHelper $coreParametersHelper,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            SmsEvents::SMS_POST_SAVE     => ['onPostSave', 0],
            SmsEvents::SMS_POST_DELETE   => ['onDelete', 0],
            SmsEvents::TOKEN_REPLACEMENT => ['onTokenReplacement', 0],
        ];
    }

    /**
     * Add an entry to the audit log.
     */
    public function onPostSave(SmsEvent $event): void
    {
        $entity = $event->getSms();
        if ($details = $event->getChanges()) {
            $log = [
                'bundle'   => 'sms',
                'object'   => 'sms',
                'objectId' => $entity->getId(),
                'action'   => ($event->isNew()) ? 'create' : 'update',
                'details'  => $details,
            ];
            $this->auditLogModel->writeToLog($log);
        }
    }

    /**
     * Add a delete entry to the audit log.
     */
    public function onDelete(SmsEvent $event): void
    {
        $entity = $event->getSms();
        $log    = [
            'bundle'   => 'sms',
            'object'   => 'sms',
            'objectId' => $entity->deletedId,
            'action'   => 'delete',
            'details'  => ['name' => $entity->getName()],
        ];
        $this->auditLogModel->writeToLog($log);
    }

    public function onTokenReplacement(TokenReplacementEvent $event): void
    {
        /** @var Lead $lead */
        $lead         = $event->getLead();
        $content      = $event->getContent();
        $clickthrough = $event->getClickthrough();

        if ($content) {
            $tokens = array_merge(
                TokenHelper::findLeadTokens($content, $lead->getProfileFields()),
                $this->pageTokenHelper->findPageTokens($content, $clickthrough),
                $this->assetTokenHelper->findAssetTokens($content, $clickthrough)
            );

            // Disable trackable urls
            if (!$this->smsHelper->getDisableTrackableUrls()) {
                [$content, $trackables] = $this->trackableModel->parseContentForTrackables(
                    $content,
                    $tokens,
                    'sms',
                    $clickthrough['channel'][1]
                );

                $shortenEnabled = $this->coreParametersHelper->get('shortener_sms_enable', false);

                /**
                 * @var string    $token
                 * @var Trackable $trackable
                 */
                foreach ($trackables as $token => $trackable) {
                    $tokens[$token] = $this->trackableModel->generateTrackableUrl($trackable, $clickthrough, $shortenEnabled);
                }
            }

            $content = str_replace(array_keys($tokens), array_values($tokens), $content);
            foreach ($tokens as $token => $value) {
                $event->addToken($token, $value);
            }

            $event->setContent($content);
        }
    }
}
