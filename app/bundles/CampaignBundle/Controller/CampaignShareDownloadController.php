<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Controller;

use Mautic\CampaignBundle\Service\CampaignShareService;
use Mautic\CoreBundle\Controller\CommonController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;

final class CampaignShareDownloadController extends CommonController
{
    #[Route(
        '/campaign-share/{token}',
        name: 'mautic_campaign_share_download',
        requirements: ['token' => '[a-f0-9]{32}'],
        methods: ['GET']
    )]
    public function downloadAction(string $token, CampaignShareService $shareService): Response
    {
        // The routing requirement constrains $token to 32 hex chars, so this is belt-and-braces
        // against any future route changes that would allow path-traversal characters through.
        if (1 !== preg_match('/^[a-f0-9]{32}$/', $token)) {
            return new Response('', Response::HTTP_NOT_FOUND);
        }

        $filePath = $shareService->getShareDir().'/'.$token.'.zip';

        if (!is_file($filePath)) {
            return new Response('', Response::HTTP_NOT_FOUND);
        }

        $mtime = filemtime($filePath);
        if (false === $mtime || (time() - $mtime) > CampaignShareService::SHARE_TTL_SECONDS) {
            @unlink($filePath);

            return new Response('', Response::HTTP_GONE);
        }

        $response = new BinaryFileResponse($filePath);
        $response->headers->set('Content-Type', 'application/zip');
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, 'campaign-share.zip');
        // One-shot: marketplace downloads the ZIP once and copies it to its own storage,
        // so we drop the local copy as soon as it's served.
        $response->deleteFileAfterSend(true);

        return $response;
    }
}
