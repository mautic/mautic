<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PageBundle\Event;

use Mautic\CoreBundle\Event\CommonEvent;
use Mautic\PageBundle\Entity\Hit;
use Mautic\PageBundle\Entity\Page;

/**
 * Class PageHitEvent
 */
class PageHitEvent extends CommonEvent
{

    /**
     * @var
     */
    private $request;

    /**
     * @var
     */
    private $code;

    /**
     * @var Page
     */
    private $page;

    /**
     * @var array
     */
    private $clickthroughData = array();

    /**
     * @param Hit   $hit
     * @param       $request
     * @param       $code
     * @param array $clickthrough
     */
    public function __construct(Hit $hit, $request, $code, $clickthrough = array())
    {
        $this->entity           = $hit;
        $this->page             = $hit->getPage();
        $this->request          = $request;
        $this->code             = $code;
        $this->clickthroughData = $clickthrough;
    }

    /**
     * Returns the Page entity
     *
     * @return Page
     */
    public function getPage()
    {
        return $this->page;
    }

    /**
     * Get page request
     *
     * @return string
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * Get HTML code
     *
     * @return mixed
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * @return Hit
     */
    public function getHit()
    {
        return $this->entity;
    }

    /**
     * @return mixed
     */
    public function getClickthroughData()
    {
        return $this->clickthroughData;
    }
}
