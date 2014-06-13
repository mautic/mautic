<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Menu;

use Knp\Menu\ItemInterface;
use Knp\Menu\Matcher\MatcherInterface;
use Knp\Menu\Renderer\RendererInterface;
use Knp\Menu\Util\MenuManipulator;
use Mautic\CoreBundle\Factory\MauticFactory;

/**
 * Class MenuRenderer
 *
 * @package Mautic\CoreBundle\Menu
 */
class MenuRenderer implements RendererInterface {
    private $engine;
    private $matcher;
    private $defaultOptions;
    private $charset;

    /**
     * @param MatcherInterface $matcher
     * @param MauticFactory    $factory
     * @param array            $defaultOptions
     */
    public function __construct( MatcherInterface $matcher, MauticFactory $factory, array $defaultOptions = array())
    {
        $this->engine           = $factory->getTemplating();
        $this->matcher          =& $matcher;
        $this->defaultOptions   = array_merge(array(
            'depth'             => null,
            'matchingDepth'     => null,
            'currentAsLink'     => true,
            'currentClass'      => 'current',
            'ancestorClass'     => 'current_ancestor',
            'firstClass'        => 'first',
            'lastClass'         => 'last',
            'template'          => "MauticCoreBundle:Menu:main.html.php",
            'compressed'        => false,
            'allow_safe_labels' => false,
            'clear_matcher'     => true,
        ), $defaultOptions);
        $this->charset          = $factory->getParam('kernel.charset');
    }

    /**
     * Renders menu
     *
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
            $html = $this->engine->render("MauticCoreBundle:Menu:breadcrumbs.html.php", array(
                "crumbs"  => $manipulator->getBreadcrumbsArray($item)
            ));
        } elseif ($options["menu"] == "admin") {
            //render html
            $html = $this->engine->render("MauticCoreBundle:Menu:admin.html.php", array(
                "item"    => $item,
                "options" => $options,
                "matcher" => $this->matcher
            ));
        } else {
            //render html
            $html = $this->engine->render("MauticCoreBundle:Menu:main.html.php", array(
                "item"    => $item,
                "options" => $options,
                "matcher" => $this->matcher
            ));
        }

        return $html;
    }
}