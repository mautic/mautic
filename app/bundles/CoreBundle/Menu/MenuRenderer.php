<?php

namespace Mautic\CoreBundle\Menu;

use Knp\Menu\ItemInterface;
use Knp\Menu\Matcher\MatcherInterface;
use Knp\Menu\Renderer\RendererInterface;
use Twig\Environment;

class MenuRenderer implements RendererInterface
{
    private array $defaultOptions;

    public function __construct(
        private MatcherInterface $matcher,
        private Environment $twig,
        array $defaultOptions = [],
    ) {
        $this->defaultOptions = array_merge(
            [
                'depth'             => null,
                'matchingDepth'     => null,
                'currentAsLink'     => true,
                'currentClass'      => 'active',
                'ancestorClass'     => 'open',
                'firstClass'        => 'first',
                'lastClass'         => 'last',
                'itemAttributes'    => [],
                'template'          => '@MauticCore/Menu/main.html.twig',
                'compressed'        => false,
                'allow_safe_labels' => false,
                'clear_matcher'     => true,
            ],
            $defaultOptions
        );
    }

    /**
     * Renders menu.
     */
    public function render(ItemInterface $item, array $options = []): string
    {
        $options = array_merge($this->defaultOptions, $options);

        if ($options['clear_matcher']) {
            $this->matcher->clear();
        }

        // render html
        $html = $this->twig->render($options['template'], [
            'item'    => $item,
            'options' => $options,
            'matcher' => $this->matcher,
        ]);

        return $html;
    }
}
