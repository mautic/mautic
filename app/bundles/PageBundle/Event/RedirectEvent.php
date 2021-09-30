<?php

/*
 * @copyright   2021 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

declare(strict_types=1);

namespace Mautic\PageBundle\Event;

use Mautic\PageBundle\Entity\Redirect;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\HttpFoundation\RedirectResponse;

class RedirectEvent extends Event
{
    private Redirect $redirect;

    private RedirectResponse $redirectResponse;

    public function __construct(Redirect $redirect)
    {
        $this->redirect = $redirect;
    }

    public function getRedirect(): Redirect
    {
        return $this->redirect;
    }

    public function getRedirectResponse(): RedirectResponse
    {
        return $this->redirectResponse;
    }

    public function setRedirectResponse(RedirectResponse $redirectResponse): void
    {
        $this->redirectResponse = $redirectResponse;
    }
}
