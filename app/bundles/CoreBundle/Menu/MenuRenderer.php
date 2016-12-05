<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Menu;

use Knp\Menu\ItemInterface;
use Knp\Menu\Matcher\MatcherInterface;
use Knp\Menu\Renderer\RendererInterface;
use Mautic\CoreBundle\Factory\MauticFactory;

/**
 * Class MenuRenderer.
 */
class MenuRenderer implements RendererInterface
{
    /**
     * @var \Symfony\Bundle\FrameworkBundle\Templating\DelegatingEngine
     */
    private $engine;

    /**
     * @var MatcherInterface
     */
    private $matcher;

    /**
     * @var array
     */
    private $defaultOptions;

    /**
     * @var string
     */
    private $charset;

    /**
     * @param MatcherInterface $matcher
     * @param MauticFactory    $factory
     * @param string           $charset
     * @param array            $defaultOptions
     */
    public function __construct(MatcherInterface $matcher, MauticFactory $factory, $charset, array $defaultOptions = [])
    {
        $this->engine         = $factory->getTemplating();
        $this->matcher        = &$matcher;
        $this->defaultOptions = array_merge([
            'depth'             => null,
            'matchingDepth'     => null,
            'currentAsLink'     => true,
            'currentClass'      => 'active',
            'ancestorClass'     => 'open',
            'firstClass'        => 'first',
            'lastClass'         => 'last',
            'template'          => 'MauticCoreBundle:Menu:main.html.php',
            'compressed'        => false,
            'allow_safe_labels' => false,
            'clear_matcher'     => true,
        ], $defaultOptions);
        $this->charset = $charset;
    }

    /**
     * Renders menu.
     *
     * @param ItemInterface $item
     * @param array         $options
     *
     * @return string
     */
    public function render(ItemInterface $item, array $options = [])
    {
        $options = array_merge($this->defaultOptions, $options);

        if ($options['clear_matcher']) {
            $this->matcher->clear();
        }

        //render html
        $html = $this->engine->render($options['template'], [
            'item'    => $item,
            'options' => $options,
            'matcher' => $this->matcher,
        ]);

        return $html;
    }
}
