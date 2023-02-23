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
     * @param array<string,string> $attributes
     * @param array<string,string> $overrides
     */
    public function parseMenuAttributes(array $attributes, array $overrides = []): string
    {
        return $this->menuHelper->parseAttributes($attributes, $overrides);
    }

    /**
     * Concats the appropriate classes for menu links.
     *
     * @param array<string,string> $options
     *
     * @return array<mixed>
     */
    public function buildMenuClasses(ItemInterface $item, ?MatcherInterface $matcher, array $options, ?string $extraClasses)
    {
        $isAncestor = (null !== $matcher) ? $matcher->isAncestor($item, (int) $options['matchingDepth']) : false;
        $isCurrent  = (null !== $matcher) ? $matcher->isCurrent($item) : false;

        $class = $item->getAttribute('class');

        $classes      = '';
        $classesArray = [];

        $classes .= ($class) ? " {$class}" : '';
        $classes .= ($extraClasses) ? " {$extraClasses}" : '';
        $classes .= ($isCurrent) ? " {$options['currentClass']}" : '';
        $classes .= ($isAncestor) ? " {$options['ancestorClass']}" : '';
        $classes .= ($isAncestor && $this->menuHelper->invisibleChildSelected($item, $matcher)) ? " {$options['currentClass']}" : '';
        $classes .= ($item->actsLikeFirst() && isset($options['firstClass'])) ? " {$options['firstClass']}" : '';
        $classes .= ($item->actsLikeLast() && isset($options['lastClass'])) ? " {$options['lastClass']}" : '';

        if ('' !== $classes) {
            $classesArray = ['class' => trim($classes)];
        }

        return $classesArray;
    }
}
