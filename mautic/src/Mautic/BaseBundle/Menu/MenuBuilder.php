<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\BaseBundle\Menu;

use Knp\Menu\FactoryInterface;
use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class MenuBuilder
 *
 * @package Mautic\BaseBundle\Menu
 */
class MenuBuilder extends ContainerAware
{
    private $factory;
    private $bundles;

    /**
     * @param FactoryInterface $factory
     * @param                  $bundles
     */
    public function __construct(FactoryInterface $factory, $bundles)
    {
        $this->factory   = $factory;
        $this->bundles   = $bundles;
    }

    /**
     * @param Request $request
     * @return \Knp\Menu\ItemInterface
     */
    public function mainMenu(Request $request)
    {
        $menu = $this->factory->createItem('root');

        foreach($this->bundles as $bundle) {
            //Load bundle menu.php if menu.php exists
            $parts = explode("\\", $bundle);
            $path  = __DIR__ . "/../../" . $parts[1] . "/Resources/config/menu.php";
            $items = array();
            if (file_exists($path)) {
                //menu.php should just be $items = array("name" => array $options);
                include_once $path;
                foreach ($items as $name => $options) {
                    $menu->addChild($name, $options);
                }
            }
        }

        return $menu;
    }
}