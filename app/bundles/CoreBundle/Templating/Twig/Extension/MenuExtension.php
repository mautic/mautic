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
}
