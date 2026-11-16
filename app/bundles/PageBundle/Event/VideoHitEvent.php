<?php

declare(strict_types=1);

namespace Mautic\PageBundle\Event;

use Mautic\CoreBundle\Event\CommonEvent;
use Mautic\PageBundle\Entity\VideoHit;
use Symfony\Component\HttpFoundation\Request;

final class VideoHitEvent extends CommonEvent
{
    public function __construct(
        VideoHit $hit,
        private readonly Request $request,
        private $code,
    ) {
        $this->entity  = $hit;
    }

    /**
     * Get page request.
     */
    public function getRequest(): Request
    {
        return $this->request;
    }

    /**
     * Get HTML code.
     *
     * @return mixed
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * @return VideoHit
     */
    public function getHit()
    {
        return $this->entity;
    }
}
