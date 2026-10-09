<?php

namespace Mautic\CampaignBundle\EventListener;

use Mautic\CampaignBundle\Event as Events;
use Mautic\CampaignBundle\Service\CampaignAuditService;
use Mautic\CoreBundle\Helper\IpLookupHelper;
use Mautic\CoreBundle\Model\AuditLogModel;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final readonly class CampaignSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private IpLookupHelper $ipLookupHelper,
        private AuditLogModel $auditLogModel,
        private CampaignAuditService $campaignAuditService,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            Events\CampaignPostSaveEvent::class    => ['onCampaignPostSave', 0],
            Events\CampaignPostDeleteEvent::class  => ['onCampaignDelete', 0],
        ];
    }

    /**
     * Add an entry to the audit log.
     */
    public function onCampaignPostSave(Events\CampaignPostSaveEvent $event): void
    {
        $campaign = $event->getCampaign();
        $details  = $event->getChanges();

        if ($campaign->isPublished()) {
            $this->campaignAuditService->addWarningForUnpublishedEmails($campaign);
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
    public function onCampaignDelete(Events\CampaignPostDeleteEvent $event): void
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
}
