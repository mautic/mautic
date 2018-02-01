<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Event;

use Symfony\Component\EventDispatcher\Event;

/**
 * Class BuildRobotsTxtEvent.
 */
class BuildRobotsTxtEvent extends Event
{
    /**
     * @var string
     */
    protected $content = '';

    /**
     * @var bool
     */
    protected $debugMode;

    /**
     * @param string $content
     * @param bool   $debugMode
     */
    public function __construct($content, $debugMode = false)
    {
        $this->content        = $content;
        $this->debugMode      = $debugMode;
    }

    /**
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * Append Content.
     *
     * @param string $content
     * @param string $section The section name. Shows when in debug mode
     *
     * @return $this
     */
    public function appendContent($content, $section = '')
    {
        if ($section && $this->debugMode) {
            $slashes = str_repeat('/', strlen($section) + 10);
            $this->content .= "
# {$section} Start
";
        }

        $this->content .= '
        '.$content;

        if ($section && $this->debugMode) {
            $this->content .= "
# {$section} End
{$slashes}
";
        }

        return $this;
    }
}
