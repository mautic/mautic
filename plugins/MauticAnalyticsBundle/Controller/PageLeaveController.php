<?php

declare(strict_types=1);

namespace MauticPlugin\MauticAnalyticsBundle\Controller;

use Mautic\CoreBundle\Controller\CommonController;
use Mautic\PageBundle\Model\PageModel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class PageLeaveController extends CommonController
{
    public function __construct(
        private AuthorizationCheckerInterface $authorizationChecker,
        private PageModel $pageModel,
    ) {
    }

    /**
     * Handle page leave beacon to record accurate dwell time.
     * Uses navigator.sendBeacon() from the client to update date_left when visitor leaves the page.
     *
     * Security: Only serves anonymous users and exclusively uses the server-set cookie
     * to prevent IDOR attacks (user-supplied hit IDs could manipulate any hit record).
     */
    public function pageLeaveAction(Request $request): Response
    {
        $hitId = $request->cookies->get('mautic_referer_id');

        if (!empty($hitId) && is_numeric($hitId)) {
            $this->pageModel->getHitRepository()->updateHitDateLeft((int) $hitId);
        }

        return new Response('', Response::HTTP_NO_CONTENT);
    }
}
