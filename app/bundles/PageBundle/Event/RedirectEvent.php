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

use Mautic\CoreBundle\Exception\InvalidDecodedStringException;
use Mautic\CoreBundle\Helper\ClickthroughHelper;
use Mautic\PageBundle\Entity\Redirect;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

class RedirectEvent extends Event
{
    private Redirect $redirect;

    private ?RedirectResponse $redirectResponse = null;

    private ?Response $contentResponse = null;

    private array $query;

    public function __construct(Redirect $redirect, array $query)
    {
        $this->redirect = $redirect;
        $this->query    = $query;
    }

    public function getRedirect(): Redirect
    {
        return $this->redirect;
    }

    public function getRedirectResponse(): ?RedirectResponse
    {
        return $this->redirectResponse;
    }

    public function setRedirectResponse(RedirectResponse $redirectResponse): void
    {
        $this->redirectResponse = $redirectResponse;
    }

    public function getQuery(): array
    {
        return $this->query;
    }

    public function getChannel()
    {
        if (isset($this->query['ct'])) {
            try {
                $clickthrough = ClickthroughHelper::decodeArrayFromUrl($this->query['ct']);

                return key($clickthrough['channel'] ?? []);
            } catch (InvalidDecodedStringException $invalidDecodedStringException) {
            }
        }
    }

    /**
     * @return Response
     */
    public function getContentResponse(): ?Response
    {
        return $this->contentResponse;
    }

    public function setContentResponse(Response $response): void
    {
        $this->contentResponse = $response;
    }
}
