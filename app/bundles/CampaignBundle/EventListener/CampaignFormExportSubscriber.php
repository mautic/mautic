<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Mautic\CoreBundle\Event\EntityExportEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class CampaignFormExportSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            EntityExportEvent::EXPORT_CAMPAIGN_FORM => ['onCampaignFormExport', 0],
        ];
    }

    public function onCampaignFormExport(EntityExportEvent $event): void
    {
        $campaignId = $event->getEntityId();

        $queryBuilder = $this->entityManager->getConnection()->createQueryBuilder();

        $formResults = $queryBuilder
            ->select('fl.form_id, ff.name, ff.category_id, ff.is_published, ff.description, ff.alias, ff.lang, ff.cached_html, ff.post_action, ff.template, ff.form_type, ff.render_style, ff.post_action_property, ff.form_attr')
            ->from(MAUTIC_TABLE_PREFIX.'campaign_form_xref', 'fl')
            ->innerJoin('fl', MAUTIC_TABLE_PREFIX.'forms', 'ff', 'ff.id = fl.form_id AND ff.is_published = 1')
            ->where('fl.campaign_id = :campaignId')
            ->setParameter('campaignId', $campaignId, \Doctrine\DBAL\ParameterType::INTEGER)
            ->executeQuery()
            ->fetchAllAssociative();

        foreach ($formResults as $result) {
            $data = [
                'id'           => $result['form_id'],
                'name'         => $result['name'],
                'is_published' => $result['is_published'],
                'category_id'  => $result['category_id'],
                'description'  => $result['description'],
                'alias'        => $result['alias'],
                'lang'         => $result['lang'],
                'cached_html'  => $result['cached_html'],
                'post_action'  => $result['post_action'],
                'template'     => $result['template'],

                'form_type'            => $result['form_type'],
                'render_style'         => $result['render_style'],
                'post_action_property' => $result['post_action_property'],
                'form_attr'            => $result['form_attr'],
            ];

            $event->addEntity(EntityExportEvent::EXPORT_CAMPAIGN_FORM, $data);
        }
    }
}
