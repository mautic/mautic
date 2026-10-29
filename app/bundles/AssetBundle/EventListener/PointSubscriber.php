<?php

declare(strict_types=1);

namespace Mautic\AssetBundle\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Mautic\AssetBundle\Event\AssetLoadEvent;
use Mautic\AssetBundle\Form\Type\PointActionAssetDownloadType;
use Mautic\AssetBundle\Helper\PointActionHelper;
use Mautic\PointBundle\Event\PointBuilderEvent;
use Mautic\PointBundle\Model\PointModel;

final readonly class PointSubscriber
{
    public function __construct(
        private PointModel $pointModel,
    ) {
    }

    #[AsEventListener(event: PointBuilderEvent::class)]
    public function onPointBuild(PointBuilderEvent $event): void
    {
        $action = [
            'group'       => 'mautic.asset.actions',
            'label'       => 'mautic.asset.point.action.download',
            'description' => 'mautic.asset.point.action.download_descr',
            'callback'    => [PointActionHelper::class, 'validateAssetDownload'],
            'formType'    => PointActionAssetDownloadType::class,
        ];

        $event->addAction('asset.download', $action);
    }

    /**
     * Trigger point actions for asset download.
     */
    #[AsEventListener(event: AssetLoadEvent::class)]
    public function onAssetDownload(AssetLoadEvent $event): void
    {
        $asset = $event->getRecord()->getAsset();

        if (null !== $asset) {
            $this->pointModel->triggerAction('asset.download', $asset);
        }
    }
}
