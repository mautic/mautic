<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\EventListener;

use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Service\CampaignAuditService;
use Mautic\CoreBundle\Event\EntityPostDelete;
use Mautic\CoreBundle\Event\EntityPostSave;
use Mautic\CoreBundle\Helper\IpLookupHelper;
use Mautic\CoreBundle\Model\AuditLogModel;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

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
    #[AsEventListener]
    public function onCampaignPostSave(EntityPostSave $event): void
    {
        $campaign = $event->getEntity();
        if (!$campaign instanceof Campaign) {
            return;
        }

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
    #[AsEventListener]
    public function onCampaignDelete(EntityPostDelete $event): void
    {
        $campaign = $event->getEntity();
        if (!$campaign instanceof Campaign) {
            return;
        }

        $log = [
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
