<?php

declare(strict_types=1);

namespace Mautic\AssetBundle\Service;

use Mautic\CoreBundle\Helper\InputHelper;
use Mautic\CoreBundle\Translation\Translator;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

final readonly class BatchDownloadResponder
{
    public function __construct(
        private Translator $translator,
    ) {
    }

    public function createResponse(string $zipPath): BinaryFileResponse
    {
        $translatedName = $this->translator->trans('mautic.asset.asset.batch_download.filename', [], 'messages');
        $downloadName   = sprintf(
            '%s-%s.zip',
            InputHelper::transliterateFilename($translatedName),
            (new \DateTimeImmutable())->format('Ymd-His')
        );

        $response = new BinaryFileResponse($zipPath);
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $downloadName);
        $response->headers->set('Content-Type', 'application/zip');
        $response->deleteFileAfterSend(true);

        return $response;
    }
}
