<?php

declare(strict_types=1);

namespace Mautic\AssetBundle\EventListener;

use Mautic\AssetBundle\Entity\Asset;
use Mautic\AssetBundle\Event\AssetExportListEvent;
use Mautic\CoreBundle\Helper\PathsHelper;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

final readonly class AssetExportListEventSubscriber
{
    public function __construct(
        private PathsHelper $pathsHelper,
    ) {
    }

    #[AsEventListener(
        event: AssetExportListEvent::class,
    )]
    public function onExportList(AssetExportListEvent $event): void
    {
        $data = $event->getEntityData();

        if ([] === $data) {
            return;
        }

        foreach ($event->getEntityData() as $section) {
            if (!is_array($section)) {
                continue;
            }

            if (!isset($section[Asset::ENTITY_NAME]) || !is_array($section[Asset::ENTITY_NAME])) {
                continue;
            }

            foreach ($section[Asset::ENTITY_NAME] as $asset) {
                $location = $asset['storage_location'] ?? null;
                $path     = $asset['path'] ?? null;

                if ('local' === $location && !empty($path)) {
                    $assetPath = $this->pathsHelper->getSystemPath('media').'/files/'.$path;
                    $event->setList($assetPath);
                }
            }
        }
    }
}
