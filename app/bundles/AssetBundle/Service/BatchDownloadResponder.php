<?php

declare(strict_types=1);

namespace Mautic\AssetBundle\Service;

use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

final class BatchDownloadResponder
{
    public function createResponse(string $zipPath): BinaryFileResponse
    {
        $downloadName = sprintf('assets-batch-%s.zip', (new \DateTimeImmutable())->format('Ymd-His'));

        $response = new BinaryFileResponse($zipPath);
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $downloadName);
        $response->headers->set('Content-Type', 'application/zip');

        register_shutdown_function(static function () use ($zipPath): void {
            if (is_file($zipPath)) {
                @unlink($zipPath);
            }
        });

        return $response;
    }
}
