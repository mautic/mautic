<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\EventListener;

use Mautic\CoreBundle\Exception\DeleteEntityDependencyException;
use Mautic\CoreBundle\Exception\RecordCanNotUnpublishException;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\IpLookupHelper;
use Mautic\CoreBundle\Model\AuditLogModel;
use Mautic\LeadBundle\Event\LeadListEvent as SegmentEvent;
use Mautic\LeadBundle\Event\ListDeleteEvent;
use Mautic\LeadBundle\Event\ListPostDeleteEvent;
use Mautic\LeadBundle\Event\ListPostSaveEvent;
use Mautic\LeadBundle\Event\ListPreDeleteEvent;
use Mautic\LeadBundle\Event\ListPreUnpublishEvent;
use Mautic\LeadBundle\Helper\SegmentCountCacheHelper;
use Mautic\LeadBundle\Model\ListModel;
use Mautic\LeadBundle\Validator\SegmentUsedInCampaignsValidator;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class SegmentSubscriber
{
    public function __construct(
        private IpLookupHelper $ipLookupHelper,
        private AuditLogModel $auditLogModel,
        private ListModel $listModel,
        private SegmentUsedInCampaignsValidator $segmentUsedInCampaignsValidator,
        private CoreParametersHelper $coreParametersHelper,
        private SegmentCountCacheHelper $segmentCountCacheHelper,
        private TranslatorInterface $translator,
    ) {
    }

    /**
     * Add a segment entry to the audit log.
     */
    #[\Symfony\Component\EventDispatcher\Attribute\AsEventListener(event: ListPostSaveEvent::class)]
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
     * @throws RecordCanNotUnpublishException
     */
    #[\Symfony\Component\EventDispatcher\Attribute\AsEventListener(event: ListPreUnpublishEvent::class)]
    public function onSegmentPreUnpublish(SegmentEvent $event): void
    {
        $leadList = $event->getList();
        $lists    = $this->listModel->getSegmentsWithDependenciesOnSegment($leadList->getId());
        if (count($lists)) {
            $message = $this->translator->trans(
                'mautic.lead_list.is_in_use.unpublish',
                [
                    '%segments%'     => implode(',', $lists),
                    '%segmentNames%' => $leadList->getName(),
                ],
                'validators');
            throw new RecordCanNotUnpublishException($message);
        }

        if ($this->validateSegmentsUsedInCampaigns($event, 'unpublish')) {
            throw new RecordCanNotUnpublishException($this->segmentUsedInCampaignsValidator->getErrorMessage());
        }
    }

    /**
     * @throws DeleteEntityDependencyException
     */
    #[\Symfony\Component\EventDispatcher\Attribute\AsEventListener(event: ListPreDeleteEvent::class)]
    public function onSegmentPreDelete(SegmentEvent $event): void
    {
        $leadList = $event->getList();
        $lists    = $this->listModel->getSegmentsWithDependenciesOnSegment($leadList->getId());

        if (count($lists)) {
            $message = $this->translator->trans(
                'mautic.lead_list.is_in_use.delete',
                [
                    '%segments%'     => implode(',', $lists),
                    '%segmentNames%' => $leadList->getName(),
                ],
                'validators');
            $event->addDependencyError($message);
        }

        if ($this->validateSegmentsUsedInCampaigns($event, 'delete')) {
            $event->addDependencyError($this->segmentUsedInCampaignsValidator->getErrorMessage());
        }
    }

    #[\Symfony\Component\EventDispatcher\Attribute\AsEventListener(event: ListDeleteEvent::class)]
    public function onSegmentDelete(SegmentEvent $event): void
    {
        if ($this->coreParametersHelper->get('delete_segment_in_background', false)) {
            return;
        }

        $list = $event->getList();

        $this->listModel->removeLeadsByListId($list->getId());
        $this->listModel->hardDeleteEntity($list);
    }

    /**
     * Add a segment delete entry to the audit log.
     */
    #[\Symfony\Component\EventDispatcher\Attribute\AsEventListener(event: ListPostDeleteEvent::class)]
    public function onSegmentPostDelete(SegmentEvent $event): void
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

    #[\Symfony\Component\EventDispatcher\Attribute\AsEventListener(event: ListPostDeleteEvent::class)]
    public function clearSegmentCountCache(SegmentEvent $event): void
    {
        $segment = $event->getList();
        $this->segmentCountCacheHelper->deleteSegmentContactCount($segment->deletedId);
    }

    private function validateSegmentsUsedInCampaigns(SegmentEvent $event, string $action): bool
    {
        return $this->segmentUsedInCampaignsValidator->validate($event->getList(), $action);
    }
}
