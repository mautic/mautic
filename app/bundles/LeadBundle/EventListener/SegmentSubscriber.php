<?php

namespace Mautic\LeadBundle\EventListener;

use Mautic\CoreBundle\Exception\RecordNotUnpublishedException;
use Mautic\CoreBundle\Helper\IpLookupHelper;
use Mautic\CoreBundle\Model\AuditLogModel;
use Mautic\LeadBundle\Event\LeadListEvent as SegmentEvent;
use Mautic\LeadBundle\LeadEvents;
use Mautic\LeadBundle\Model\ListModel;
use Mautic\LeadBundle\Validator\SegmentUsedInCampaignsValidator;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class SegmentSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private IpLookupHelper $ipLookupHelper,
        private AuditLogModel $auditLogModel,
        private ListModel $listModel,
        private SegmentUsedInCampaignsValidator $segmentUsedInCampaignsValidator,
        private TranslatorInterface $translator,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            LeadEvents::LIST_POST_SAVE     => ['onSegmentPostSave', 0],
            LeadEvents::LIST_POST_DELETE   => ['onSegmentDelete', 0],
            LeadEvents::LIST_PRE_UNPUBLISH => [
                ['validateSegmentFilters', 0],
                ['validateSegmentsUsedInCampaigns', 0],
            ],
        ];
    }

    /**
     * Add a segment entry to the audit log.
     */
    public function onSegmentPostSave(SegmentEvent $event): void
    {
        $segment = $event->getList();
        if ($details = $event->getChanges()) {
            $log = [
                'bundle'    => 'lead',
                'object'    => 'segment',
                'objectId'  => $segment->getId(),
                'action'    => ($event->isNew()) ? 'create' : 'update',
                'details'   => $details,
                'ipAddress' => $this->ipLookupHelper->getIpAddressFromRequest(),
            ];
            $this->auditLogModel->writeToLog($log);
        }
    }

    /**
     * @throws RecordNotUnpublishedException
     */
    public function validateSegmentFilters(SegmentEvent $event): void
    {
        $leadList = $event->getList();
        $lists    = $this->listModel->getSegmentsWithDependenciesOnSegment($leadList->getId(), 'name');
        if (count($lists)) {
            $message = $this->translator->trans('mautic.lead_list.is_in_use', ['%segments%' => implode(',', $lists)], 'validators');
            throw new RecordNotUnpublishedException($message);
        }
    }

    /**
     * Add a segment delete entry to the audit log.
     */
    public function onSegmentDelete(SegmentEvent $event): void
    {
        $segment = $event->getList();
        $log     = [
            'bundle'    => 'lead',
            'object'    => 'segment',
            'objectId'  => $segment->deletedId,
            'action'    => 'delete',
            'details'   => ['name', $segment->getName()],
            'ipAddress' => $this->ipLookupHelper->getIpAddressFromRequest(),
        ];
        $this->auditLogModel->writeToLog($log);
    }

    /**
     * @throws RecordNotUnpublishedException
     */
    public function validateSegmentsUsedInCampaigns(SegmentEvent $event): void
    {
        $this->segmentUsedInCampaignsValidator->validate($event->getList());
    }
}
