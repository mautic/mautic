<?php

namespace Mautic\PageBundle\Event;

use Mautic\CoreBundle\Event\CommonEvent;
use Mautic\PageBundle\Entity\VideoHit;

class VideoHitEvent extends CommonEvent
{
    public function __construct(
        VideoHit $hit,
        protected $request,
        protected $code
    ) {
        $this->entity  = $hit;
    }

    /**
     * Get page request.
     *
     * @return string
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * Get HTML code.
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
