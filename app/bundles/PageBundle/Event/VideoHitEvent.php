<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PageBundle\Event;

use Mautic\CoreBundle\Event\CommonEvent;
use Mautic\PageBundle\Entity\VideoHit;

/**
 * Class PageHitEvent.
 */
class VideoHitEvent extends CommonEvent
{
    /**
     * @var
     */
    protected $request;

    /**
     * @var
     */
    protected $code;

    /**
     * PageHitEvent constructor.
     *
     * @param VideoHit $hit
     * @param          $request
     * @param          $code
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
