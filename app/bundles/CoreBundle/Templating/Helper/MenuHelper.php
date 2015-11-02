<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Templating\Helper;

use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\UserBundle\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Knp\Menu\Twig\Helper as KnpHelper;
use Knp\Menu\Matcher\MatcherInterface;
use Knp\Menu\ItemInterface;
use Symfony\Component\Templating\Helper\Helper;

/**
 * Class MenuHelper
 */
class MenuHelper extends Helper
{

    /**
     * @var KnpHelper
     */
    protected $helper;

    /**
     * @var CorePermissions
     */
    protected $security;

    /**
     * @var null|Request
     */
    protected $request;

    /**
     * @var User
     */
    protected $user;

    /**
     * @var array
     */
    protected $mauticParameters;

    public function __construct(KnpHelper $helper, CorePermissions $security, TokenStorageInterface $tokenStorage, RequestStack $requestStack, array $mauticParameters)
    {
        $this->helper           = $helper;
        $this->security         = $security;
        $this->user             = $tokenStorage->getToken()->getUser();
        $this->mauticParameters = $mauticParameters;
        $this->request          = $requestStack->getCurrentRequest();
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'menu';
    }

    /**
     * Parses attributes for the menu view
     *
     * @param $attributes
     * @param $overrides
     *
     * @return string
     */
    public function parseAttributes($attributes, $overrides = array())
    {
        if (!is_array($attributes)) {
            $attributes = array();
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
     * Concats the appropriate classes for menu links
     *
     * @param ItemInterface    $item
     * @param MatcherInterface $matcher
     * @param array            $options
     */
    public function buildClasses(ItemInterface &$item, MatcherInterface &$matcher, $options)
    {
        $isAncestor   = $matcher->isAncestor($item, $options['matchingDepth']);
        $isCurrent    = $matcher->isCurrent($item);

        $class    = $item->getAttribute('class');
        $classes  = ($class) ? " {$class}" : "";
        $classes .= ($isCurrent) ? " {$options['currentClass']}" : "";
        $classes .= ($isAncestor) ? " {$options['ancestorClass']}" : "";
        $classes .= ($isAncestor && $this->invisibleChildSelected($item, $matcher)) ? " {$options['currentClass']}" : "";
        $classes .= ($item->actsLikeFirst()) ? " {$options['firstClass']}" : "";
        $classes .= ($item->actsLikeLast()) ? " {$options['lastClass']}" : "";
        $item->setAttribute("class", trim($classes));
    }

    /**
     * @param ItemInterface    $menu
     * @param MatcherInterface $matcher
     *
     * @return bool
     */
    public function invisibleChildSelected($menu, MatcherInterface $matcher)
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
     * Converts menu config into something KNP menus expects
     *
     * @param      $items
     * @param int  $depth
     * @param bool $isRoot
     */
    public function createMenuStructure(&$items, $depth = 0, $isRoot = false)
    {
        if ($isRoot) {
            if (!empty($items['children'])) {
                $this->createMenuStructure($items['children'], $depth + 1);
            }
        } else {
            foreach ($items as $k => &$i) {
                if (!is_array($i) || empty($i)) {
                    continue;
                }

                if (isset($i['bundle'])) {
                    // Category shortcut
                    $bundleName = $i['bundle'];
                    $i          = array(
                        'access'          => $bundleName.':categories:view',
                        'route'           => 'mautic_category_index',
                        'id'              => 'mautic_'.$bundleName.'category_index',
                        'routeParameters' => array('bundle' => $bundleName),
                    );
                }

                // Check to see if menu is restricted
                if (isset($i['access'])) {
                    if ($i['access'] == 'admin') {
                        if (!$this->user->isAdmin()) {
                            unset($items[$k]);
                            continue;
                        }
                    } elseif (!$this->security->isGranted($i['access'], 'MATCH_ONE')) {
                        unset($items[$k]);
                        continue;
                    }
                }

                if (isset($i['checks'])) {
                    $passChecks = true;
                    foreach ($i['checks'] as $checkGroup => $checks) {
                        foreach ($checks as $name => $value) {
                            if ($checkGroup == 'parameters') {
                                if ($this->getParameter($name) != $value) {
                                    $passChecks = false;
                                    break;
                                }
                            } elseif ($checkGroup == 'request') {
                                if ($this->request->get($name) != $value) {
                                    $passChecks = false;
                                    break;
                                }
                            }
                        }
                    }
                    if (!$passChecks) {
                        unset($items[$k]);
                        continue;
                    }
                }

                //Set ID to route name
                if (!isset($i['id'])) {
                    if (!empty($i['route'])) {
                        $i['id'] = $i['route'];
                    } else {
                        $i['id'] = 'menu-item-'.uniqid();
                    }
                }

                //Set link attributes
                if (!isset($i['linkAttributes'])) {
                    $i['linkAttributes'] = array(
                        'data-menu-link' => $i['id'],
                        'id'             => $i['id']
                    );
                } elseif (!isset($i['linkAttributes']['id'])) {
                    $i['linkAttributes']['id']             = $i['id'];
                    $i['linkAttributes']['data-menu-link'] = $i['id'];
                } elseif (!isset($i['linkAttributes']['data-menu-link'])) {
                    $i['linkAttributes']['data-menu-link'] = $i['id'];
                }

                $i['extras']          = array();
                $i['extras']['depth'] = $depth;

                // Note a divider
                if (!empty($i['divider'])) {
                    $i['extras']['divider'] = true;
                }

                // Note a header
                if (!empty($i['header'])) {
                    $i['extras']['header'] = $i['header'];
                }

                //Set the icon class for the menu item
                if (!empty($i['iconClass'])) {
                    $i['extras']['iconClass'] = $i['iconClass'];
                }

                //Set the actual route name so that it's available to the menu template
                if (isset($i['route'])) {
                    $i['extras']['routeName'] = $i['route'];
                }

                //Repeat for sub items
                if (isset($i['children'])) {
                    $this->createMenuStructure($i['children'], $depth + 1);
                }
            }
        }
    }

    /**
     * Retrieves an item following a path in the tree.
     *
     * @param \Knp\Menu\ItemInterface|string $menu
     * @param array                          $path
     * @param array                          $options
     *
     * @return \Knp\Menu\ItemInterface
     */
    public function get($menu, array $path = array(), array $options = array())
    {
        return $this->helper->get($menu, $path, $options);
    }

    /**
     * Renders a menu with the specified renderer.
     *
     * @param \Knp\Menu\ItemInterface|string|array $menu
     * @param array                                $options
     * @param string                               $renderer
     *
     * @return string
     */
    public function render($menu, array $options = array(), $renderer = null)
    {
        if (null === $renderer) {
            $renderer = $menu;
        }
        $options['menu'] = $menu;

        return $this->helper->render($menu, $options, $renderer);
    }

    /**
     * @param $name
     *
     * @return bool
     */
    protected function getParameter($name)
    {
        return isset($this->mauticParameters[$name]) ? $this->mauticParameters[$name] : false;
    }
}
