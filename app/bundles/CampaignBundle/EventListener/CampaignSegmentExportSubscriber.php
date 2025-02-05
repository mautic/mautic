<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Mautic\CoreBundle\Event\EntityExportEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class CampaignSegmentExportSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            EntityExportEvent::EXPORT_CAMPAIGN_SEGMENT => ['onCampaignSegmentExport', 0],
        ];
    }

    public function onCampaignSegmentExport(EntityExportEvent $event): void
    {
        $campaignId = $event->getEntityId();

        $queryBuilder = $this->entityManager->getConnection()->createQueryBuilder();

        $segmentResults = $queryBuilder
            ->select('cl.leadlist_id, ll.name, ll.category_id, ll.is_published, ll.description, ll.alias, ll.public_name, ll.filters, ll.is_global, ll.is_preference_center')
            ->from('campaign_leadlist_xref', 'cl')
            ->innerJoin('cl', 'lead_lists', 'll', 'll.id = cl.leadlist_id AND ll.is_published = 1')
            ->where('cl.campaign_id = :campaignId')
            ->setParameter('campaignId', $campaignId, \Doctrine\DBAL\ParameterType::INTEGER)
            ->executeQuery()
            ->fetchAllAssociative();

        foreach ($segmentResults as $segmentResult) {
            $segmentData = [
                'id'                   => $segmentResult['leadlist_id'],
                'name'                 => $segmentResult['name'],
                'is_published'         => $segmentResult['is_published'],
                'category_id'          => $segmentResult['category_id'],
                'description'          => $segmentResult['description'],
                'alias'                => $segmentResult['alias'],
                'public_name'          => $segmentResult['public_name'],
                'filters'              => $segmentResult['filters'],
                'is_global'            => $segmentResult['is_global'],
                'is_preference_center' => $segmentResult['is_preference_center'],
            ];
            $dependency = [
                'campaignId' => (int) $campaignId,
                'segmentId'  => (int) $segmentResult['leadlist_id'],
            ];
            $event->addEntity(EntityExportEvent::EXPORT_CAMPAIGN_SEGMENT, $segmentData);
            $event->addDependencyEntity(EntityExportEvent::EXPORT_CAMPAIGN_SEGMENT, $dependency);
        }
    }
}
