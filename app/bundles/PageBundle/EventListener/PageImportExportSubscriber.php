<?php

declare(strict_types=1);

namespace Mautic\PageBundle\EventListener;

use Mautic\CoreBundle\Event\EntityExportEvent;
use Mautic\PageBundle\Model\PageModel;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class PageImportExportSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private PageModel $pageModel,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            EntityExportEvent::EXPORT_PAGE => ['onPageExport', 0],
        ];
    }

    public function onPageExport(EntityExportEvent $event): void
    {
        $pageId = $event->getEntityId();
        $page   = $this->pageModel->getEntity($pageId);
        if (!$page) {
            return;
        }

        $pageData = [
            'id'                   => $page->getId(),
            // 'category_id'          => $page->getCategory() ? $page->getCategory()->getId() : null,
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
            'is_preference_center' => $page->isPreferenceCenter(),
            'no_index'             => $page->getNoIndex(),
            'lang'                 => $page->getLanguage(),
            'variant_settings'     => $page->getVariantSettings(),
        ];

        $event->addEntity(EntityExportEvent::EXPORT_PAGE, $pageData);
    }
}
