<?php

declare(strict_types=1);

namespace Mautic\DynamicContentBundle\EventListener;

use Mautic\AssetBundle\Event\AssetExportListEvent;
use Mautic\DynamicContentBundle\Entity\DynamicContent;
use Mautic\EmailBundle\Helper\EmailMediaImageHelper;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Dynamic content HTML can embed builder images by absolute URL, the same way emails and
 * landing pages do. Those images are not Asset entities, so without this listener they
 * would be missing from the export ZIP and show up broken after import.
 */
final readonly class DynamicContentExportListEventSubscriber implements EventSubscriberInterface
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
            if (!is_array($section) || !isset($section[DynamicContent::ENTITY_NAME]) || !is_array($section[DynamicContent::ENTITY_NAME])) {
                continue;
            }

            foreach ($section[DynamicContent::ENTITY_NAME] as $dynamicContent) {
                if (!is_array($dynamicContent)) {
                    continue;
                }

                foreach ($this->mediaImageHelper->extractLocalImageFiles($this->collectSearchableContent($dynamicContent)) as $filePath) {
                    $event->setList($filePath);
                }
            }
        }
    }

    /**
     * @param array<string, mixed> $dynamicContent
     */
    private function collectSearchableContent(array $dynamicContent): string
    {
        $content = $dynamicContent['content'] ?? null;

        if (is_string($content)) {
            return $content;
        }

        // Builder-based dynamic content stores a structured array; flattening it to JSON
        // lets us reuse the same URL matching without walking the tree.
        if (is_array($content) && [] !== $content) {
            $encoded = json_encode($content, JSON_UNESCAPED_SLASHES);
            if (false !== $encoded) {
                return $encoded;
            }
        }

        return '';
    }
}
