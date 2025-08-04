<?php

declare(strict_types=1);

namespace Mautic\PageBundle\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Mautic\CoreBundle\Event\EntityExportEvent;
use Mautic\CoreBundle\Event\EntityImportAnalyzeEvent;
use Mautic\CoreBundle\Event\EntityImportEvent;
use Mautic\CoreBundle\Event\EntityImportUndoEvent;
use Mautic\CoreBundle\EventListener\ImportExportTrait;
use Mautic\CoreBundle\Helper\IpLookupHelper;
use Mautic\CoreBundle\Model\AuditLogModel;
use Mautic\PageBundle\Entity\Page;
use Mautic\PageBundle\Model\PageModel;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

final class PageImportExportSubscriber implements EventSubscriberInterface
{
    use ImportExportTrait;

    public function __construct(
        private PageModel $pageModel,
        private EntityManagerInterface $entityManager,
        private AuditLogModel $auditLogModel,
        private IpLookupHelper $ipLookupHelper,
        private DenormalizerInterface $serializer,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            EntityExportEvent::class        => ['onPageExport', 0],
            EntityImportEvent::class        => ['onPageImport', 0],
            EntityImportUndoEvent::class    => ['onUndoImport', 0],
            EntityImportAnalyzeEvent::class => ['onDuplicationCheck', 0],
        ];
    }

    public function onPageExport(EntityExportEvent $event): void
    {
        if (Page::ENTITY_NAME !== $event->getEntityName()) {
            return;
        }

        $pageId = $event->getEntityId();
        $page   = $this->pageModel->getEntity($pageId);
        if (!$page) {
            return;
        }

        $pageData = [
            'id'                   => $page->getId(),
            'is_published'         => $page->isPublished(),
            'title'                => $page->getTitle(),
            'alias'                => $page->getAlias(),
            'template'             => $page->getTemplate(),
            'custom_html'          => $page->getCustomHtml(),
            'content'              => $page->getContent(),
            'publish_up'           => $page->getPublishUp() ? $page->getPublishUp()->format(DATE_ATOM) : null,
            'publish_down'         => $page->getPublishDown() ? $page->getPublishDown()->format(DATE_ATOM) : null,
            'hits'                 => $page->getHits(),
            'unique_hits'          => $page->getUniqueHits(),
            'variant_hits'         => $page->getVariantHits(),
            'revision'             => $page->getRevision(),
            'meta_description'     => $page->getMetaDescription(),
            'head_script'          => $page->getHeadScript(),
            'footer_script'        => $page->getFooterScript(),
            'redirect_type'        => $page->getRedirectType(),
            'redirect_url'         => $page->getRedirectUrl(),
            'is_preference_center' => $page->getIsPreferenceCenter(),
            'no_index'             => $page->getNoIndex(),
            'lang'                 => $page->getLanguage(),
            'variant_settings'     => $page->getVariantSettings(),
            'uuid'                 => $page->getUuid(),
        ];

        $event->addEntity(Page::ENTITY_NAME, $pageData);
        $this->logAction('export', $page->getId(), $pageData);
    }

    public function onPageImport(EntityImportEvent $event): void
    {
        if (Page::ENTITY_NAME !== $event->getEntityName() || !$event->getEntityData()) {
            return;
        }

        $stats = [
            EntityImportEvent::NEW    => ['names' => [], 'ids' => [], 'count' => 0],
            EntityImportEvent::UPDATE => ['names' => [], 'ids' => [], 'count' => 0],
        ];

        foreach ($event->getEntityData() as $element) {
            $object = $this->entityManager->getRepository(Page::class)->findOneBy(['uuid' => $element['uuid']]);
            $isNew  = !$object;

            $object ??= new Page();
            $this->serializer->denormalize(
                $element,
                Page::class,
                null,
                ['object_to_populate' => $object]
            );
            $this->pageModel->saveEntity($object);

            $event->addEntityIdMap((int) $element['id'], $object->getId());

            $status                    = $isNew ? EntityImportEvent::NEW : EntityImportEvent::UPDATE;
            $stats[$status]['names'][] = $object->getTitle();
            $stats[$status]['ids'][]   = $object->getId();
            ++$stats[$status]['count'];

            $this->logAction('import', $object->getId(), $element);
        }

        foreach ($stats as $status => $info) {
            if ($info['count'] > 0) {
                $event->setStatus($status, [Page::ENTITY_NAME => $info]);
            }
        }
    }

    public function onUndoImport(EntityImportUndoEvent $event): void
    {
        if (Page::ENTITY_NAME !== $event->getEntityName()) {
            return;
        }

        $summary  = $event->getSummary();

        if (!isset($summary['ids']) || empty($summary['ids'])) {
            return;
        }
        foreach ($summary['ids'] as $id) {
            $entity = $this->entityManager->getRepository(Page::class)->find($id);

            if ($entity) {
                $this->entityManager->remove($entity);
                $this->logAction('undo_import', $id, ['deletedEntity' => Page::class]);
            }
        }

        $this->entityManager->flush();
    }

    public function onDuplicationCheck(EntityImportAnalyzeEvent $event): void
    {
        $this->performDuplicationCheck(
            $event,
            Page::ENTITY_NAME,
            Page::class,
            'title',
            $this->entityManager
        );
    }

    /**
     * @param array<string, mixed> $details
     */
    private function logAction(string $action, int $objectId, array $details): void
    {
        $this->auditLogModel->writeToLog([
            'bundle'    => 'page',
            'object'    => 'page',
            'objectId'  => $objectId,
            'action'    => $action,
            'details'   => $details,
            'ipAddress' => $this->ipLookupHelper->getIpAddressFromRequest(),
        ]);
    }
}
