<?php

declare(strict_types=1);

namespace Mautic\AssetBundle\Service;

use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Symfony\Component\HttpFoundation\Request;

final class BatchDownloadRequestValidator
{
    public function __construct(
        private CorePermissions $security,
    ) {
    }

    public function validatePermissions(): bool
    {
        $permissions = $this->security->isGranted([
            'asset:assets:viewown',
            'asset:assets:viewother',
        ], 'RETURN_ARRAY');

        return $permissions['asset:assets:viewown'] || $permissions['asset:assets:viewother'];
    }

    /**
     * @return array<int>
     */
    public function validateAndExtractIds(Request $request): array
    {
        $idsPayload = $request->get('ids', '');

        if ('' === $idsPayload) {
            throw new \InvalidArgumentException('mautic.asset.asset.batch_download.error.no_selection');
        }

        $ids = json_decode((string) $idsPayload, true);

        if (!is_array($ids) || empty($ids)) {
            throw new \InvalidArgumentException('mautic.asset.asset.batch_download.error.no_selection');
        }

        $validIds = [];

        foreach ($ids as $id) {
            if (!is_scalar($id)) {
                throw new \InvalidArgumentException('mautic.asset.asset.batch_download.error.no_selection');
            }

            $validIds[] = (int) $id;
        }

        return $validIds;
    }
}
