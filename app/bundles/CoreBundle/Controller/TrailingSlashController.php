<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Controller;

use Mautic\CoreBundle\Helper\TrailingSlashHelper;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class TrailingSlashController extends AbstractController
{
    // Redirects URLs with trailing slashes in order to prevent 404s.
    public function removeTrailingSlashAction(Request $request, TrailingSlashHelper $trailingSlashHelper): RedirectResponse
    {
        return $this->redirect($trailingSlashHelper->getSafeRedirectUrl($request), Response::HTTP_MOVED_PERMANENTLY);
    }
}
