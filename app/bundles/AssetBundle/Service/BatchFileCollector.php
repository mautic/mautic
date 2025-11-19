<?php

declare(strict_types=1);

namespace Mautic\AssetBundle\Service;

use Mautic\AssetBundle\Entity\Asset;
use Mautic\AssetBundle\Model\AssetModel;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;

final class BatchFileCollector
{
    public function __construct(
        private AssetModel $assetModel,
        private CorePermissions $security,
        private CoreParametersHelper $parametersHelper,
    ) {
    }

    /**
     * @param array<int> $ids
     *
     * @return array<Asset>
     */
    public function collectDownloadableAssets(array $ids): array
    {
        $downloadableAssets = [];

        foreach ($ids as $id) {
            $asset = $this->assetModel->getEntity($id);

            if (null === $asset) {
                throw new \InvalidArgumentException('mautic.asset.asset.batch_download.error.not_found');
            }

            if (!$this->security->hasEntityAccess(
                'asset:assets:viewown',
                'asset:assets:viewother',
                $asset->getCreatedBy()
            )) {
                throw new \InvalidArgumentException('mautic.asset.asset.batch_download.error.permission');
            }

            if ($asset->isLocal()) {
                $asset->setUploadDir($this->parametersHelper->get('upload_dir'));
                $absolutePath = $asset->getAbsolutePath();

                if (empty($absolutePath) || !is_file($absolutePath) || !is_readable($absolutePath)) {
                    throw new \InvalidArgumentException('mautic.asset.asset.batch_download.error.unavailable');
                }
            }

            $downloadableAssets[] = $asset;
        }

        if (0 === count($downloadableAssets)) {
            throw new \InvalidArgumentException('mautic.asset.asset.batch_download.error.none_available');
        }

        return $downloadableAssets;
    }
}
