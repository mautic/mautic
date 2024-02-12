<?php

namespace Mautic\CoreBundle\Twig\Helper;

use Knp\Menu\ItemInterface;
use Knp\Menu\Matcher\MatcherInterface;
use Knp\Menu\Twig\Helper as KnpHelper;

/**
 * final class MenuHelper.
 */
final class MenuHelper
{
    public function __construct(
        private KnpHelper $helper
    ) {
    }

    public function getName(): string
    {
        return 'menu';
    }

    /**
     * Parses attributes for the menu view.
     *
     * @param array<string, mixed> $attributes
     * @param array<string, mixed> $overrides
     */
    public function parseAttributes($attributes, $overrides = []): string
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
     * @param array<string, mixed> $options
     */
    public function buildClasses(ItemInterface &$item, MatcherInterface &$matcher, $options): void
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
     * @param ItemInterface $menu
     */
    public function invisibleChildSelected($menu, MatcherInterface $matcher): bool
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
     * @param array<int, string>             $path
     * @param array<string, mixed>           $options
     */
    public function get($menu, array $path = [], array $options = []): ItemInterface
    {
        return $this->helper->get($menu, $path, $options);
    }

    /**
     * Renders a menu with the specified renderer.
     *
     * @param \Knp\Menu\ItemInterface|string|array<ItemInterface|string> $menu
     * @param array<string, mixed>                                       $options
     * @param string                                                     $renderer
     */
    public function render($menu, array $options = [], $renderer = null): string
    {
        if (null === $renderer) {
            $renderer = $menu;
        }
        $options['menu'] = $menu;

        return $this->helper->render($menu, $options, $renderer);
    }
}
