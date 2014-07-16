<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PageBundle\Event;

use Mautic\CoreBundle\Event\CommonEvent;
use Mautic\PageBundle\Entity\Page;

/**
 * Class PageHitEvent
 *
 * @package Mautic\PageBundle\Event
 */
class PageHitEvent extends CommonEvent
{

    private $request;
    private $code;

    /**
     * @param Page $page
     */
    public function __construct(Page $page, $request, $code)
    {
        $this->entity  = $page;
        $this->request = $request;
        $this->code    = $code;
    }

    /**
     * Returns the Page entity
     *
     * @return Page
     */
    public function getPage()
    {
        return $this->entity;
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
}