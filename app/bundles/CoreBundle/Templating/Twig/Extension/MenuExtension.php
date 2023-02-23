<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Templating\Twig\Extension;

use Knp\Menu\ItemInterface;
use Knp\Menu\Matcher\MatcherInterface;
use Mautic\CoreBundle\Templating\Helper\MenuHelper;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class MenuExtension extends AbstractExtension
{
    protected MenuHelper $menuHelper;

    public function __construct(MenuHelper $menuHelper)
    {
        $this->menuHelper = $menuHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new TwigFunction('menuRender', [$this, 'menuRender'], ['is_safe' => ['all']]),
            new TwigFunction('parseMenuAttributes', [$this, 'parseMenuAttributes'], ['is_safe' => ['all']]),
            new TwigFunction('buildMenuClasses', [$this, 'buildMenuClasses'], ['is_safe' => ['all']]),
        ];
    }

    /**
     * Renders a menu with the specified renderer.
     *
     * @param \Knp\Menu\ItemInterface|string|array<mixed> $menu
     * @param array<mixed>                                $options
     */
    public function menuRender($menu, array $options = [], ?string $renderer = null): string
    {
        return $this->menuHelper->render($menu, $options, $renderer);
    }

    /**
     * Parses attributes for the menu view.
     *
     * @param array<string> $attributes
     * @param array<string> $overrides
     */
    public function parseMenuAttributes($attributes, $overrides = []): string
    {
        return $this->menuHelper->parseAttributes($attributes, $overrides);
    }

    /**
     * Concats the appropriate classes for menu links.
     *
     * @param ItemInterface|null    $item
     * @param MatcherInterface|null $matcher
     * @param array<string,string>  $options
     * @param string                $extra
     *
     * @return array<mixed>
     */
    public function buildMenuClasses($item, $matcher, $options, $extra)
    {
        $isAncestor = $matcher !== null ? $matcher->isAncestor($item, (int) $options['matchingDepth']) : false;
        $isCurrent  = $matcher !== null ? $matcher->isCurrent($item) : false;

        $class = !empty($item) ? $item->getAttribute('class') : '';

        $classes = '';
        $classesArray = [];

        $classes .= ($class) ? " {$class}" : '';
        $classes .= ($extra) ? " {$extra}" : '';
        $classes .= ($isCurrent) ? " {$options['currentClass']}" : '';
        $classes .= ($isAncestor) ? " {$options['ancestorClass']}" : '';
        $classes .= ($isAncestor && $this->menuHelper->invisibleChildSelected($item, $matcher)) ? " {$options['currentClass']}" : '';
        $classes .= ($item->actsLikeFirst() && isset($options['firstClass'])) ? " {$options['firstClass']}" : '';
        $classes .= ($item->actsLikeLast() && isset($options['lastClass'])) ? " {$options['lastClass']}" : '';

        if ($classes !== '' ) {
            $classesArray = ['class' => trim($classes)];
        }

        return $classesArray;
    }
}
