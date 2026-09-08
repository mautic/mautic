<?php

declare(strict_types=1);

namespace Mautic\PageBundle\EventListener;

use Mautic\AssetBundle\Event\AssetExportListEvent;
use Mautic\EmailBundle\Helper\EmailMediaImageHelper;
use Mautic\PageBundle\Entity\Page;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Builder-embedded images in landing pages are referenced by absolute URL inside the page
 * HTML/builder JSON, just like in emails. They are not Asset entities, so the Asset export
 * listener never collects them and the export ZIP would ship without the images. This
 * listener scans the exported page content for such media images and adds the local files
 * to the export asset list so they are packed alongside the entity data.
 */
final readonly class PageExportListEventSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private EmailMediaImageHelper $mediaImageHelper,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            AssetExportListEvent::class => ['onExportList', 0],
        ];
    }

    public function onExportList(AssetExportListEvent $event): void
    {
        foreach ($event->getEntityData() as $section) {
            if (!is_array($section) || !isset($section[Page::ENTITY_NAME]) || !is_array($section[Page::ENTITY_NAME])) {
                continue;
            }

            foreach ($section[Page::ENTITY_NAME] as $page) {
                if (!is_array($page)) {
                    continue;
                }

                foreach ($this->mediaImageHelper->extractLocalImageFiles($this->collectSearchableContent($page)) as $filePath) {
                    $event->setList($filePath);
                }
            }
        }
    }

    /**
     * @param array<string, mixed> $page
     */
    private function collectSearchableContent(array $page): string
    {
        $parts = [];

        if (!empty($page['custom_html']) && is_string($page['custom_html'])) {
            $parts[] = $page['custom_html'];
        }

        // The builder stores its document as a structured array under "content"; flattening it to
        // JSON lets us reuse the same URL matching without walking the tree.
        if (!empty($page['content'])) {
            $encoded = json_encode($page['content'], JSON_UNESCAPED_SLASHES);
            if (false !== $encoded) {
                $parts[] = $encoded;
            }
        }

        return implode("\n", $parts);
    }
}
