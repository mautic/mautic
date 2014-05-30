<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Menu;

use Symfony\Component\Templating\Helper\Helper;
use Knp\Menu\Matcher\MatcherInterface;
use Knp\Menu\ItemInterface;
use Symfony\Bundle\FrameworkBundle\Templating\DelegatingEngine;

/**
 * Class MenuHelper
 */
class MenuHelper extends Helper {

    /**
     * @return string
     */
    public function getName() {
        return "menu_helper";
    }

    /**
     * Parses attributes for the menu view
     *
     * @param $attributes
     * @param $overrides
     * @return string
     */
    public function parseAttributes($attributes, $overrides = array()) {
        if (!is_array($attributes))
            $attributes = array();

        $attributes = array_merge($attributes, $overrides);

        $string = "";
        foreach ($attributes as $name => $value) {
            $name  = trim($name);
            $value = trim($value);
            if ($name == $value)
                $string .= " $name";
            else $string .= " $name=\"$value\"";
        }
        return $string;
    }

    /**
     * Concats the appropriate classes for menu links
     *
     * @param ItemInterface    $item
     * @param MatcherInterface $matcher
     * @param                  $options
     */
    public function buildClasses(ItemInterface &$item, MatcherInterface &$matcher, $options) {
        $class   = $item->getAttribute("class");
        $classes = ($class) ? " {$class}" : "";
        $classes .= ($matcher->isCurrent($item)) ? " {$options["currentClass"]}" : "";
        $classes .= ($matcher->isAncestor($item, $options["matchingDepth"])) ? " {$options["ancestorClass"]}" : "";
        $classes .= ($item->actsLikeFirst()) ? " {$options["firstClass"]}" : "";
        $classes .= ($item->actsLikeLast()) ? " {$options["lastClass"]}" : "";
        $item->setAttribute("class", trim($classes));
    }
}