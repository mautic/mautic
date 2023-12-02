<?php

namespace Mautic\PageBundle\Event;

use Mautic\PageBundle\Entity\Page;
use Symfony\Contracts\EventDispatcher\Event;

class PageDisplayEvent extends Event
{
    /**
     * @var string
     */
    private $content;

    private \Mautic\PageBundle\Entity\Page $page;

    private array $params;

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
