<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Templating\Helper;

use Symfony\Component\Templating\Helper\SlotsHelper as BaseSlotsHelper;

/**
 * Class SlotsHelper
 */
class SlotsHelper extends BaseSlotsHelper
{
    /**
     * Appends a slot value if already set
     *
     * @param $name
     * @param $content
     */
    public function append($name, $content)
    {
        if (isset($this->slots[$name])) {
            if (is_array($this->slots[$name])) {
                $this->slots[$name][] = $content;
            } else {
                $this->slots[$name] .= " " . $content;
            }
        } else {
            $this->slots[$name] = $content;
        }
    }

    /**
     * Checks if the slot has some content when a page is viewed in public.
     *
     * @param string|array $names
     */
    public function hasContent($names)
    {
        if (!$this->slots['public']) {
            return true;
        }

        if (is_string($names)) {
            $names = array($names);
        }

        if (is_array($names)) {
            foreach ($names as $n) {
                // strip tags used to ensure we don't have empty tags.
                // Caused a bug with hasContent returning incorrectly. Whitelisted img to fix
                $hasContent = (boolean) strip_tags(trim($this->slots[$n]), "<img>");
                if ($hasContent) {
                    return true;
                }
            }
        }
        
        return false;
    }
}
