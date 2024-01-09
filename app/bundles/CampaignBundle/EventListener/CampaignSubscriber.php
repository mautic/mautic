<?php

namespace Mautic\CampaignBundle\EventListener;

use Mautic\CampaignBundle\CampaignEvents;
use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Event as Events;
use Mautic\CampaignBundle\Service\Campaign as CampaignService;
use Mautic\CoreBundle\Helper\IpLookupHelper;
use Mautic\CoreBundle\Model\AuditLogModel;
use Mautic\CoreBundle\Service\FlashBag;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CampaignSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private IpLookupHelper $ipLookupHelper,
        private AuditLogModel $auditLogModel,
        private CampaignService $campaignService,
        private FlashBag $flashBag
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CampaignEvents::CAMPAIGN_POST_SAVE     => ['onCampaignPostSave', 0],
            CampaignEvents::CAMPAIGN_POST_DELETE   => ['onCampaignDelete', 0],
        ];
    }

    /**
     * Add an entry to the audit log.
     */
    public function onCampaignPostSave(Events\CampaignEvent $event): void
    {
        $campaign = $event->getCampaign();
        $details  = $event->getChanges();

        if ($campaign->isPublished() && $this->campaignService->hasUnpublishedEmail($campaign->getId())) {
            $this->setUnpublishedMailFlashMessage($campaign);
        }

        // don't set leads
        unset($details['leads']);

        if (!empty($details)) {
            $log = [
                'bundle'    => 'campaign',
                'object'    => 'campaign',
                'objectId'  => $campaign->getId(),
                'action'    => ($event->isNew()) ? 'create' : 'update',
                'details'   => $details,
                'ipAddress' => $this->ipLookupHelper->getIpAddressFromRequest(),
            ];
            $this->auditLogModel->writeToLog($log);
        }
    }

    /**
     * Add a delete entry to the audit log.
     */
    public function onCampaignDelete(Events\CampaignEvent $event): void
    {
        $campaign = $event->getCampaign();
        $log      = [
            'bundle'    => 'campaign',
            'object'    => 'campaign',
            'objectId'  => $campaign->deletedId,
            'action'    => 'delete',
            'details'   => ['name' => $campaign->getName()],
            'ipAddress' => $this->ipLookupHelper->getIpAddressFromRequest(),
        ];
        $this->auditLogModel->writeToLog($log);
    }

    private function setUnpublishedMailFlashMessage(Campaign $campaign): void
    {
        $this->flashBag->add(
            'mautic.core.notice.campaign.unpublished.email',
            [
                '%name%' => $campaign->getName(),
            ]
        );
    }
}
