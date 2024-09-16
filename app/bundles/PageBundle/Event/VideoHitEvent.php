<?php

namespace Mautic\PageBundle\Event;

use Mautic\CoreBundle\Event\CommonEvent;
use Mautic\PageBundle\Entity\VideoHit;

class VideoHitEvent extends CommonEvent
{
    protected $request;

    protected $code;

    /**
     * @param $request
     * @param $code
     */
    public function __construct(VideoHit $hit, $request, $code)
    {
        $this->entity  = $hit;
        $this->request = $request;
        $this->code    = $code;
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
