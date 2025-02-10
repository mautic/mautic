<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\EventListener;

use Mautic\CoreBundle\Event\EntityExportEvent;
use Mautic\LeadBundle\Model\ListModel;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class SegmentImportExportSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private ListModel $leadListModel,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            EntityExportEvent::EXPORT_SEGMENT => ['onSegmentExport', 0],
        ];
    }

    public function onSegmentExport(EntityExportEvent $event): void
    {
        $leadListId = $event->getEntityId();
        $leadList   = $this->leadListModel->getEntity($leadListId);
        if (!$leadList) {
            return;
        }
        $segmentData = [
            'id'                   => $leadListId,
            'name'                 => $leadList->getName(),
            'is_published'         => $leadList->getIsPublished(),
            'description'          => $leadList->getDescription(),
            'alias'                => $leadList->getAlias(),
            'public_name'          => $leadList->getPublicName(),
            'filters'              => $leadList->getFilters(),
            'is_global'            => $leadList->getIsGlobal(),
            'is_preference_center' => $leadList->getIsPreferenceCenter(),
        ];
        $event->addEntity(EntityExportEvent::EXPORT_SEGMENT, $segmentData);
    }
}
