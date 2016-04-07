<?php
/**
 * @package     Mautic
 * @copyright   2016 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Event;

use Symfony\Component\EventDispatcher\Event;

/**
 * Class BuildJsEvent
 *
 * @package Mautic\PageBundle\Event
 */
class BuildJsEvent extends Event
{
    /**
     * @var string
     */
    protected $js;

    /**
     * @param string $js
     */
    public function __construct($js = '')
    {
        $this->js = $js;
    }

    /**
     * @return string
     */
    public function getJs()
    {
        return $this->js;
    }

    /**
     * @param string $section
     * @param string $js
     */
    public function appendJs($section, $js)
    {
        $slashes = str_repeat('/', strlen($section) + 10);
        $this->js .= "\n\n";
        $this->js .= <<<JS
{$slashes}
// {$section} Start
{$slashes}
\n
JS;
        $this->js .= $js;
        $this->js .= <<<JS
\n
{$slashes}
// {$section} End
{$slashes}
\n
JS;

    }
}