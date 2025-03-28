<?php

declare(strict_types=1);

namespace Mautic\PageBundle\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Mautic\CoreBundle\Event\EntityExportEvent;
use Mautic\CoreBundle\Event\EntityImportAnalyzeEvent;
use Mautic\CoreBundle\Event\EntityImportEvent;
use Mautic\CoreBundle\Event\EntityImportUndoEvent;
use Mautic\CoreBundle\Helper\IpLookupHelper;
use Mautic\CoreBundle\Model\AuditLogModel;
use Mautic\PageBundle\Entity\Page;
use Mautic\PageBundle\Model\PageModel;
use Mautic\UserBundle\Model\UserModel;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class PageImportExportSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private PageModel $pageModel,
        private UserModel $userModel,
        private EntityManagerInterface $entityManager,
        private AuditLogModel $auditLogModel,
        private IpLookupHelper $ipLookupHelper,
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

        $userName = '';
        if ($event->getUserId()) {
            $user     = $this->userModel->getEntity($event->getUserId());
            $userName = $user ? $user->getFirstName().' '.$user->getLastName() : '';
        }

        $stats = [
            EntityImportEvent::NEW    => ['names' => [], 'ids' => [], 'count' => 0],
            EntityImportEvent::UPDATE => ['names' => [], 'ids' => [], 'count' => 0],
        ];

        foreach ($event->getEntityData() as $element) {
            $page  = $this->entityManager->getRepository(Page::class)->findOneBy(['uuid' => $element['uuid']]);
            $isNew = !$page;

            $page ??= new Page();
            $isNew && $page->setDateAdded(new \DateTime());
            $page->setDateModified(new \DateTime());
            $page->setUuid($element['uuid']);
            $page->setTitle($element['title']);
            $page->setIsPublished((bool) $element['is_published']);
            $page->setAlias($element['alias'] ?? '');
            $page->setTemplate($element['template'] ?? '');
            $page->setContent($element['content'] ?? '');
            $page->setCustomHtml($element['custom_html'] ?? '');
            $page->setPublishUp($element['publish_up']);
            $page->setPublishDown($element['publish_down']);
            $page->setHits($element['hits']);
            $page->setUniqueHits($element['unique_hits']);
            $page->setVariantHits($element['variant_hits']);
            $page->setRevision($element['revision']);
            $page->setLanguage($element['lang'] ?? '');
            $page->setMetaDescription($element['meta_description'] ?? '');
            $page->setHeadScript($element['head_script'] ?? '');
            $page->setFooterScript($element['footer_script'] ?? '');
            $page->setRedirectType($element['redirect_type'] ?? '');
            $page->setRedirectUrl($element['redirect_url']);
            $page->setIsPreferenceCenter($element['is_preference_center'] ?? false);
            $page->setNoIndex($element['no_index'] ?? false);
            $page->setVariantSettings($element['variant_settings'] ?? []);

            $isNew ? $page->setCreatedByUser($userName) : $page->setModifiedByUser($userName);

            $this->entityManager->persist($page);
            $this->entityManager->flush();

            $event->addEntityIdMap((int) $element['id'], $page->getId());

            $status                    = $isNew ? EntityImportEvent::NEW : EntityImportEvent::UPDATE;
            $stats[$status]['names'][] = $page->getTitle();
            $stats[$status]['ids'][]   = $page->getId();
            ++$stats[$status]['count'];

            $this->logAction('import', $page->getId(), $element);
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
        if (Page::ENTITY_NAME !== $event->getEntityName() || empty($event->getEntityData())) {
            return;
        }

        $summary = [
            EntityImportEvent::NEW    => ['names' => [], 'count' => 0],
            EntityImportEvent::UPDATE => ['names' => [], 'ids' => [], 'count' => 0],
        ];

        foreach ($event->getEntityData() as $item) {
            $existing = $this->entityManager->getRepository(Page::class)->findOneBy(['uuid' => $item['uuid']]);
            if ($existing) {
                $summary[EntityImportEvent::UPDATE]['names'][] = $existing->getTitle();
                $summary[EntityImportEvent::UPDATE]['ids'][]   = $existing->getId();
                ++$summary[EntityImportEvent::UPDATE]['count'];
            } else {
                $summary[EntityImportEvent::NEW]['names'][] = $item['title'];
                ++$summary[EntityImportEvent::NEW]['count'];
            }
        }

        foreach ($summary as $type => $data) {
            if ($data['count'] > 0) {
                $event->setSummary($type, [Page::ENTITY_NAME => $data]);
            }
        }
    }

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
