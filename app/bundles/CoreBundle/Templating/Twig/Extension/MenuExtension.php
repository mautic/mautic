<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Templating\Twig\Extension;

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
     * @param $attributes
     * @param $overrides
     */
    public function parseMenuAttributes($attributes, $overrides = []): string
    {
        return $this->menuHelper->parseAttributes($attributes, $overrides);
    }

    /**
     * Concats the appropriate classes for menu links.
     *
     * @param array $options
     */
    public function buildMenuClasses($item, $matcher, $options, $extra): array
    {
        $isAncestor = $matcher->isAncestor($item, $options['matchingDepth']);
        $isCurrent  = $matcher->isCurrent($item);

        $class   = $item->getAttribute('class');

        $classes = ($class) ? " {$class}" : '';
        $classes .= ($extra) ? " {$extra}" : '';
        $classes .= ($isCurrent) ? " {$options['currentClass']}" : '';
        $classes .= ($isAncestor) ? " {$options['ancestorClass']}" : '';
        $classes .= ($isAncestor && $this->invisibleChildSelected($item, $matcher)) ? " {$options['currentClass']}" : '';
        $classes .= ($item->actsLikeFirst()) ? " {$options['firstClass']}" : '';
        $classes .= ($item->actsLikeLast()) ? " {$options['lastClass']}" : '';

        return ['class' => trim($classes)];
    }
}
