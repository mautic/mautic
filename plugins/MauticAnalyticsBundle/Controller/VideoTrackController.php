<?php

declare(strict_types=1);

namespace MauticPlugin\MauticAnalyticsBundle\Controller;

use Mautic\PageBundle\Model\VideoModel;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class VideoTrackController
{
    public function __construct(
        private VideoModel $videoModel,
    ) {
    }

    /**
     * Handle video tracking request.
     * Tracks video play events with watch time and duration.
     */
    public function trackAction(Request $request): Response
    {
        // Only track XMLHttpRequests for security
        if (!$request->isXmlHttpRequest()) {
            return new Response('', Response::HTTP_BAD_REQUEST);
        }

        $guid = $request->request->get('guid');
        $url  = $request->request->get('url');

        if (empty($guid) || empty($url)) {
            return new JsonResponse(['error' => 'Missing required parameters'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $this->videoModel->hitVideo($request);

            return new JsonResponse(['success' => true]);
        } catch (\Exception $e) {
            return new JsonResponse(['success' => false, 'error' => 'Failed to track video']);
        }
    }
}
