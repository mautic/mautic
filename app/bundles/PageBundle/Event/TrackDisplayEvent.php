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

use Symfony\Component\EventDispatcher\Event;

/**
 * Class TrackDisplayEvent.
 */
class TrackDisplayEvent extends Event
{
    /**
     * @var string
     */
    private $content;

    /**
     * PageDisplayEvent constructor.
     *
     * @param      $content
     */
    public function __construct($content)
    {
        $this->content = $content;
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
}
