<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\EventListener;

use Mautic\AssetBundle\Event\AssetExportListEvent;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Helper\EmailMediaImageHelper;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Builder-embedded images (uploaded through the file manager into the media images directory) are
 * referenced by absolute URL inside the email HTML/builder JSON. They are not Asset entities, so the
 * Asset export listener never collects them and the export ZIP ends up without the images. This
 * listener scans the exported email content for such media images and adds the local files to the
 * export asset list so they are packed alongside the entity data.
 */
final readonly class EmailExportListEventSubscriber implements EventSubscriberInterface
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
            if (!is_array($section) || !isset($section[Email::ENTITY_NAME]) || !is_array($section[Email::ENTITY_NAME])) {
                continue;
            }

            foreach ($section[Email::ENTITY_NAME] as $email) {
                if (!is_array($email)) {
                    continue;
                }

                foreach ($this->mediaImageHelper->extractLocalImageFiles($this->collectSearchableContent($email)) as $filePath) {
                    $event->setList($filePath);
                }
            }
        }
    }

    /**
     * @param array<string, mixed> $email
     */
    private function collectSearchableContent(array $email): string
    {
        $parts = [];

        if (!empty($email['custom_html']) && is_string($email['custom_html'])) {
            $parts[] = $email['custom_html'];
        }

        // The builder stores its document as a structured array under "content"; flattening it to
        // JSON lets us reuse the same URL matching without walking the tree.
        if (!empty($email['content'])) {
            $encoded = json_encode($email['content'], JSON_UNESCAPED_SLASHES);
            if (false !== $encoded) {
                $parts[] = $encoded;
            }
        }

        return implode("\n", $parts);
    }
}
