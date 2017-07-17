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

use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\PluginBundle\Helper\IntegrationHelper;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class MenuHelper.
 */
class MenuHelper
{
    /**
     * @var CorePermissions
     */
    protected $security;

    /**
     * @var null|Request
     */
    protected $request;

    /**
     * Stores items that are assigned to another parent outside it's bundle.
     *
     * @var array
     */
    private $orphans = [];

    /**
     * @var array
     */
    protected $mauticParameters;

    /**
     * @var IntegrationHelper
     */
    protected $integrationHelper;

    /**
     * MenuHelper constructor.
     *
     * @param CorePermissions   $security
     * @param RequestStack      $requestStack
     * @param array             $mauticParameters
     * @param IntegrationHelper $integrationHelper
     */
    public function __construct(CorePermissions $security, RequestStack $requestStack, array $mauticParameters, IntegrationHelper $integrationHelper)
    {
        $this->security          = $security;
        $this->mauticParameters  = $mauticParameters;
        $this->request           = $requestStack->getCurrentRequest();
        $this->integrationHelper = $integrationHelper;
    }

    /**
     * Converts menu config into something KNP menus expects.
     *
     * @param        $items
     * @param int    $depth
     * @param int    $defaultPriority
     * @param string $type
     */
    public function createMenuStructure(&$items, $depth = 0, $defaultPriority = 9999, $type = 'main')
    {
        foreach ($items as $k => &$i) {
            if (!is_array($i) || empty($i)) {
                continue;
            }

            // Remove the item if the checks fail
            if ($this->handleChecks($i) === false) {
                unset($items[$k]);
                continue;
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
                $i['linkAttributes'] = [
                    'data-menu-link' => $i['id'],
                    'id'             => $i['id'],
                ];
            } elseif (!isset($i['linkAttributes']['id'])) {
                $i['linkAttributes']['id']             = $i['id'];
                $i['linkAttributes']['data-menu-link'] = $i['id'];
            } elseif (!isset($i['linkAttributes']['data-menu-link'])) {
                $i['linkAttributes']['data-menu-link'] = $i['id'];
            }

            $i['extras']          = [];
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
                $this->createMenuStructure($i['children'], $depth + 1, $defaultPriority);
            }

            // Determine if this item needs to be listed in a bundle outside it's own
            if (isset($i['parent'])) {
                if (!isset($this->orphans[$type])) {
                    $this->orphans[$type] = [];
                }

                if (!isset($this->orphans[$type][$i['parent']])) {
                    $this->orphans[$type][$i['parent']] = [];
                }

                $this->orphans[$type][$i['parent']][$k] = $i;

                unset($items[$k]);

                // Don't set a default priority here as it'll assume that of it's parent
            } elseif (!isset($i['priority'])) {
                // Ensure a priority for non-orphans
                $i['priority'] = $defaultPriority;
            }
        }
    }

    /**
     * Get and reset orphaned menu items.
     *
     * @param string $type
     *
     * @return mixed
     */
    public function resetOrphans($type = 'main')
    {
        $orphans              = (isset($this->orphans[$type])) ? $this->orphans[$type] : [];
        $this->orphans[$type] = [];

        return $orphans;
    }

    /**
     * Give orphaned menu items a home.
     *
     * @param array $menuItems
     * @param bool  $appendOrphans
     * @param int   $depth
     */
    public function placeOrphans(array &$menuItems, $appendOrphans = false, $depth = 1, $type = 'main')
    {
        foreach ($menuItems as $key => &$items) {
            if (isset($this->orphans[$type]) && isset($this->orphans[$type][$key])) {
                $priority = (isset($items['priority'])) ? $items['priority'] : 9999;
                foreach ($this->orphans[$type][$key] as &$orphan) {
                    if (!isset($orphan['extras'])) {
                        $orphan['extras'] = [];
                    }
                    $orphan['extras']['depth'] = $depth;
                    if (!isset($orphan['priority'])) {
                        $orphan['priority'] = $priority;
                    }
                }

                $items['children'] =
                    (!isset($items['children']))
                    ?
                    $this->orphans[$type][$key]
                    :
                    array_merge($items['children'], $this->orphans[$type][$key]);
                unset($this->orphans[$type][$key]);
            } elseif (isset($items['children'])) {
                foreach ($items['children'] as $subKey => $subItems) {
                    $this->placeOrphans($subItems, false, $depth + 1, $type);
                }
            }
        }

        // Append orphans that couldn't find a home
        if ($appendOrphans && !empty($this->orphans[$type])) {
            $menuItems            = array_merge($menuItems, $this->orphans[$type]);
            $this->orphans[$type] = [];
        }
    }

    /**
     * Sort menu items by priority.
     *
     * @param $menuItems
     * @param $defaultPriority
     */
    public function sortByPriority(&$menuItems, $defaultPriority = 9999)
    {
        foreach ($menuItems as &$items) {
            $parentPriority = (isset($items['priority'])) ? $items['priority'] : $defaultPriority;
            if (isset($items['children'])) {
                $this->sortByPriority($items['children'], $parentPriority);
            }
        }

        uasort(
            $menuItems,
            function ($a, $b) use ($defaultPriority) {
                $ap = (isset($a['priority']) ? (int) $a['priority'] : $defaultPriority);
                $bp = (isset($b['priority']) ? (int) $b['priority'] : $defaultPriority);

                if ($ap == $bp) {
                    return 0;
                }

                return ($ap > $bp) ? -1 : 1;
            }
        );
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

    /**
     * @param string $integrationName
     * @param array  $config
     *
     * @return bool
     */
    protected function handleIntegrationChecks($integrationName, array $config)
    {
        $integration = $this->integrationHelper->getIntegrationObject($integrationName);

        if (!$integration) {
            return false;
        }

        $settings = $integration->getIntegrationSettings();

        $passChecks = true;

        foreach ($config as $key => $value) {
            switch ($key) {
                case 'enabled':
                    $passChecks = $settings->getIsPublished() == $value;
                    break;
                case 'features':
                    $supportedFeatures = $settings->getSupportedFeatures();

                    foreach ($value as $featureName) {
                        if (!in_array($featureName, $supportedFeatures)) {
                            $passChecks = false;
                            break;
                        }
                    }
                    break;
            }
        }

        return $passChecks;
    }

    /**
     * @param string $name
     * @param mixed  $value
     *
     * @return bool
     */
    protected function handleParametersChecks($name, $value)
    {
        return $this->getParameter($name) == $value;
    }

    /**
     * @param string $name
     * @param mixed  $value
     *
     * @return bool
     */
    protected function handleRequestChecks($name, $value)
    {
        return $this->request->get($name) == $value;
    }

    /**
     * @param $accessLevel
     *
     * @return bool
     */
    protected function handleAccessCheck($accessLevel)
    {
        switch ($accessLevel) {
            case 'admin':
                return $this->security->isAdmin();
            default:
                return $this->security->isGranted($accessLevel, 'MATCH_ONE');
        }
    }

    /**
     * Handle access check and other checks for menu items.
     *
     * @param array $menuItem
     *
     * @return bool Returns false if the item fails the access check or any other checks
     */
    protected function handleChecks(array $menuItem)
    {
        if (isset($menuItem['access']) && $this->handleAccessCheck($menuItem['access']) === false) {
            return false;
        }

        if (isset($menuItem['checks']) && is_array($menuItem['checks'])) {
            foreach ($menuItem['checks'] as $checkGroup => $checkConfig) {
                $checkMethod = 'handle'.ucfirst($checkGroup).'Checks';

                if (!method_exists($this, $checkMethod)) {
                    continue;
                }

                foreach ($checkConfig as $name => $value) {
                    if ($this->$checkMethod($name, $value) === false) {
                        return false;
                    }
                }
            }
        }

        return true;
    }
}
