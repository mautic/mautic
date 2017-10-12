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

use Mautic\PageBundle\Entity\Page;
use Symfony\Component\EventDispatcher\Event;

/**
 * Class PageDisplayEvent.
 */
class PageDisplayEvent extends Event
{
    /**
     * @var string
     */
    private $content;

    /**
     * @var Page
     */
    private $page;

    /**
     * @var array
     */
    private $params;

    /**
     * PageDisplayEvent constructor.
     *
     * @param $content
     * @param Page  $page
     * @param array $params
     */
    public function __construct($content, Page $page, array $params = [])
    {
        $this->page    = $page;
        $this->content = $content;
        $this->params  = $params;
    }

    /**
     * Returns the Page entity.
     *
     * @return Page
     */
    public function getPage()
    {
        return $this->page;
    }

    /**
     * Get page content.
     *
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * Set page content.
     *
     * @param string $content
     */
    public function setContent($content)
    {
        $this->content = $content;
    }

    /**
     * Get params.
     *
     * @return array
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * Set params.
     *
     * @param array $params
     */
    public function setParams($params)
    {
        $this->params = $params;
    }
}
