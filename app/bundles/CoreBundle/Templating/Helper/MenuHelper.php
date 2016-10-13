<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Templating\Helper;

use Knp\Menu\ItemInterface;
use Knp\Menu\Matcher\MatcherInterface;
use Knp\Menu\Twig\Helper as KnpHelper;
use Symfony\Component\Templating\Helper\Helper;

/**
 * Class MenuHelper.
 */
class MenuHelper extends Helper
{
    /**
     * @var KnpHelper
     */
    protected $helper;

    /**
     * MenuHelper constructor.
     *
     * @param KnpHelper $helper
     */
    public function __construct(KnpHelper $helper)
    {
        $this->helper = $helper;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'menu';
    }

    /**
     * Parses attributes for the menu view.
     *
     * @param $attributes
     * @param $overrides
     *
     * @return string
     */
    public function parseAttributes($attributes, $overrides = [])
    {
        if (!is_array($attributes)) {
            $attributes = [];
        }

        $attributes = array_merge($attributes, $overrides);

        $string = '';
        foreach ($attributes as $name => $value) {
            $name  = trim($name);
            $value = trim($value);
            if ($name == $value) {
                $string .= " $name";
            } else {
                $string .= " $name=\"$value\"";
            }
        }

        return $string;
    }

    /**
     * Concats the appropriate classes for menu links.
     *
     * @param ItemInterface    $item
     * @param MatcherInterface $matcher
     * @param array            $options
     */
    public function buildClasses(ItemInterface &$item, MatcherInterface &$matcher, $options)
    {
        $isAncestor = $matcher->isAncestor($item, $options['matchingDepth']);
        $isCurrent  = $matcher->isCurrent($item);

        $class   = $item->getAttribute('class');
        $classes = ($class) ? " {$class}" : '';
        $classes .= ($isCurrent) ? " {$options['currentClass']}" : '';
        $classes .= ($isAncestor) ? " {$options['ancestorClass']}" : '';
        $classes .= ($isAncestor && $this->invisibleChildSelected($item, $matcher)) ? " {$options['currentClass']}" : '';
        $classes .= ($item->actsLikeFirst()) ? " {$options['firstClass']}" : '';
        $classes .= ($item->actsLikeLast()) ? " {$options['lastClass']}" : '';
        $item->setAttribute('class', trim($classes));
    }

    /**
     * @param ItemInterface    $menu
     * @param MatcherInterface $matcher
     *
     * @return bool
     */
    public function invisibleChildSelected($menu, MatcherInterface $matcher)
    {
        /** @var ItemInterface $item */
        foreach ($menu as $item) {
            if ($matcher->isCurrent($item)) {
                return ($item->isDisplayed()) ? false : true;
            }
        }

        return false;
    }

    /**
     * Retrieves an item following a path in the tree.
     *
     * @param \Knp\Menu\ItemInterface|string $menu
     * @param array                          $path
     * @param array                          $options
     *
     * @return \Knp\Menu\ItemInterface
     */
    public function get($menu, array $path = [], array $options = [])
    {
        return $this->helper->get($menu, $path, $options);
    }

    /**
     * Renders a menu with the specified renderer.
     *
     * @param \Knp\Menu\ItemInterface|string|array $menu
     * @param array                                $options
     * @param string                               $renderer
     *
     * @return string
     */
    public function render($menu, array $options = [], $renderer = null)
    {
        if (null === $renderer) {
            $renderer = $menu;
        }
        $options['menu'] = $menu;

        return $this->helper->render($menu, $options, $renderer);
    }
}
