<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\BaseBundle\Menu;

use Knp\Menu\ItemInterface;
use Knp\Menu\Matcher\MatcherInterface;
use Knp\Menu\Renderer\RendererInterface;
use Knp\Menu\Util\MenuManipulator;
use Symfony\Bundle\FrameworkBundle\Templating\DelegatingEngine;

/**
 * Class MenuRenderer
 *
 * @package Mautic\BaseBundle\Menu
 */
class MenuRenderer implements RendererInterface {
    private $engine;
    private $matcher;
    private $defaultOptions;
    private $charset;

    /**
     * @param DelegatingEngine $engine
     * @param MatcherInterface $matcher
     * @param                  $charset
     * @param array            $defaultOptions
     */
    public function __construct(DelegatingEngine $engine, MatcherInterface $matcher, $charset, array $defaultOptions = array())
    {
        $this->engine           = $engine;
        $this->matcher          = $matcher;
        $this->defaultOptions   = array_merge(array(
            'depth'             => null,
            'matchingDepth'     => null,
            'currentAsLink'     => true,
            'currentClass'      => 'current',
            'ancestorClass'     => 'current_ancestor',
            'firstClass'        => 'first',
            'lastClass'         => 'last',
            'template'          => "menu.main.html.php",
            'compressed'        => false,
            'allow_safe_labels' => false,
            'clear_matcher'     => true,
        ), $defaultOptions);
        $this->charset          = $charset;
    }

    /**
     * @param ItemInterface $item
     * @param array         $options
     * @return string
     */
    public function render(ItemInterface $item, array $options = array())
    {
        $options = array_merge($this->defaultOptions, $options);

        if ($options['clear_matcher']) {
            $this->matcher->clear();
        }
        $manipulator = new MenuManipulator();
        if ($options["menu"] == "breadcrumbs") {
            $html = $this->engine->render("MauticBaseBundle:Default:menu.breadcrumbs.html.php", array(
                "crumbs"  => $manipulator->getBreadcrumbsArray($item)
            ));
        } else {
            //render html
            $html = $this->engine->render("MauticBaseBundle:Default:menu.main.html.php", array(
                "item"    => $item,
                "options" => $options,
                "matcher" => $this->matcher
            ));
        }

        return $html;
    }
}