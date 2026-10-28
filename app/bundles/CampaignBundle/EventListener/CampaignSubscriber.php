<?php

namespace Mautic\CampaignBundle\EventListener;

use Mautic\CampaignBundle\Event as Events;
use Mautic\CampaignBundle\Service\CampaignAuditService;
use Mautic\CoreBundle\Helper\IpLookupHelper;
use Mautic\CoreBundle\Model\AuditLogModel;

final readonly class CampaignSubscriber
{
    public function __construct(
        private IpLookupHelper $ipLookupHelper,
        private AuditLogModel $auditLogModel,
        private CampaignAuditService $campaignAuditService,
    ) {
    }
    /**
     * Add an entry to the audit log.
     */
    #[\Symfony\Component\EventDispatcher\Attribute\AsEventListener(
        event: Events\CampaignPostSaveEvent::class,
        priority: 0
    )]
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
    #[\Symfony\Component\EventDispatcher\Attribute\AsEventListener(
        event: Events\CampaignPostDeleteEvent::class,
        priority: 0
    )]
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
