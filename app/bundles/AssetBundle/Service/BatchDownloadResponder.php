<?php

declare(strict_types=1);

namespace Mautic\AssetBundle\Service;

use Mautic\CoreBundle\Translation\Translator;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Contracts\Service\Attribute\Required;

final class BatchDownloadResponder
{
    private Translator $translator;

    #[Required]
    public function setTranslator(Translator $translator): void
    {
        $this->translator = $translator;
    }

    public function createResponse(string $zipPath): BinaryFileResponse
    {
        $translatedName = $this->translator->trans('mautic.asset.asset.batch_download.filename', [], 'messages');
        $downloadName   = sprintf('%s-%s.zip', $translatedName, (new \DateTimeImmutable())->format('Ymd-His'));

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
