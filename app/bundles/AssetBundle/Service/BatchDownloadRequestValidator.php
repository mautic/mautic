<?php

declare(strict_types=1);

namespace Mautic\AssetBundle\Service;

use Mautic\AssetBundle\Service\Exception\BatchDownloadException;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

final readonly class BatchDownloadRequestValidator
{
    public const MAX_ASSETS_PER_REQUEST = 100;

    public const CSRF_TOKEN_ID = 'mautic_asset_batch_download';

    public function __construct(
        private CorePermissions $security,
        private CsrfTokenManagerInterface $csrfTokenManager,
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

    public function hasValidCsrfToken(Request $request): bool
    {
        $token = $request->request->get('_token');

        if (!is_string($token) || '' === $token) {
            return false;
        }

        return $this->csrfTokenManager->isTokenValid(new CsrfToken(self::CSRF_TOKEN_ID, $token));
    }

    /**
     * @return array<int, int>
     */
    public function validateAndExtractIds(Request $request): array
    {
        $idsPayload = $request->get('ids', '');

        if ('' === $idsPayload) {
            throw new BatchDownloadException('mautic.asset.asset.batch_download.error.no_selection');
        }

        try {
            $ids = json_decode((string) $idsPayload, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            throw new BatchDownloadException('mautic.asset.asset.batch_download.error.no_selection');
        }

        if (!is_array($ids) || !array_is_list($ids) || [] === $ids) {
            throw new BatchDownloadException('mautic.asset.asset.batch_download.error.no_selection');
        }

        if (count($ids) > self::MAX_ASSETS_PER_REQUEST) {
            throw new BatchDownloadException('mautic.asset.asset.batch_download.error.too_many');
        }

        $validIds = [];

        foreach ($ids as $id) {
            if (!is_int($id) && (!is_string($id) || !ctype_digit($id))) {
                throw new BatchDownloadException('mautic.asset.asset.batch_download.error.no_selection');
            }

            $validId = (int) $id;
            if ($validId <= 0) {
                throw new BatchDownloadException('mautic.asset.asset.batch_download.error.no_selection');
            }

            $validIds[] = $validId;
        }

        return $validIds;
    }
}
