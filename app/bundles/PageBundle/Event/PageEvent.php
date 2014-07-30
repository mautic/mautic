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
 * Class PageEvent
 *
 * @package Mautic\PageBundle\Event
 */
class PageEvent extends CommonEvent
{

    private $content;
    private $slotsHelper;

    /**
     * @param Page $page
     * @param bool $isNew
     */
    public function __construct(Page &$page, $isNew = false)
    {
        $this->entity  =& $page;
        $this->content = $page->getContent();
        $this->isNew   = $isNew;
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
     * Sets the Page entity
     *
     * @param Page $page
     */
    public function setPage(Page $page)
    {
        $this->entity = $page;
    }

    /**
     * Get page content
     *
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * Set page content
     *
     * @param array $content
     */
    public function setContent(array $content)
    {
        $this->content = $content;
    }

    /**
     * Set the slots helper for content
     *
     * @param $slotsHelper
     */
    public function setSlotsHelper($slotsHelper)
    {
        $this->slotsHelper = $slotsHelper;
    }

    /**
     * Get the slots helper that can be used to add scripts/stylesheets to the header
     *
     * @return mixed
     */
    public function getSlotsHelper()
    {
        return $this->slotsHelper;
    }
}