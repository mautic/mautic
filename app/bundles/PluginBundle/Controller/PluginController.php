<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PluginBundle\Controller;

use Mautic\CoreBundle\Controller\FormController;
use Mautic\PluginBundle\Entity\Plugin;

/**
 * Class PluginController
 */
class PluginController extends FormController
{
    /**
     * Scans the addon bundles directly and loads bundles which are not registered to the database
     *
     * @param int $objectId Unused in this action
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    protected function reloadAction($objectId)
    {
        if (!$this->factory->getSecurity()->isGranted('plugin:plugins:manage')) {
            return $this->accessDenied();
        }

        /** @var \Mautic\PluginBundle\Model\PluginModel $model */
        $model = $this->factory->getModel('plugin');
        /** @var \Mautic\PluginBundle\Entity\PluginRepository $repo */
        $repo    = $model->getRepository();
        $plugins = $this->factory->getParameter('plugin.bundles');
        $added   = $disabled = $updated = 0;

        $installedPlugins = $repo->getInstalled();

        $persist = array();
        /**
         * @var string $bundle
         * @var Plugin $plugin
         */
        foreach ($installedPlugins as $bundle => $plugin) {
            $persistUpdate = false;
            if (!isset($plugins[$bundle])) {
                if (!$plugin->getIsMissing()) {
                    //files are no longer found
                    $plugin->setIsEnabled(false);
                    $plugin->setIsMissing(true);
                    $disabled++;
                }
            } else {
                if ($plugin->getIsMissing()) {
                    //was lost but now is found
                    $plugin->setIsMissing(false);
                    $persistUpdate = true;
                }

                $file = $plugins[$bundle]['directory'].'/Config/config.php';

                //update details of the bundle
                if (file_exists($file)) {
                    /** @var array $details */
                    $details = include $file;

                    //compare versions to see if an update is necessary
                    $version = isset($details['version']) ? $details['version'] : '';
                    if (!empty($version) && version_compare($plugin->getVersion(), $version) == -1) {
                        $updated++;

                        //call the update callback
                        $callback = $plugins[$bundle]['bundleClass'];
                        $callback::onUpdate($plugin, $this->factory);
                        $persistUpdate = true;
                    }

                    $plugin->setVersion($version);

                    $plugin->setName(
                        isset($details['name']) ? $details['name'] : $plugins[$bundle]['base']
                    );

                    if (isset($details['description'])) {
                        $plugin->setDescription($details['description']);
                    }

                    if (isset($details['author'])) {
                        $plugin->setAuthor($details['author']);
                    }
                }

                unset($plugins[$bundle]);
            }
            if ($persistUpdate) {
                $persist[] = $plugin;
            }
        }

        //rest are new
        foreach ($plugins as $plugin) {
            $added++;
            $entity = new Plugin();
            $entity->setBundle($plugin['bundle']);
            $entity->setIsEnabled(false);

            $file = $plugin['directory'].'/Config/config.php';

            //update details of the bundle
            if (file_exists($file)) {
                $details = include $file;

                if (isset($details['version'])) {
                    $entity->setVersion($details['version']);
                };

                $entity->setName(
                    isset($details['name']) ? $details['name'] : $plugin['base']
                );

                if (isset($details['description'])) {
                    $entity->setDescription($details['description']);
                }

                if (isset($details['author'])) {
                    $entity->setAuthor($details['author']);
                }
            }

            //call the install callback
            $callback = $plugin['bundleClass'];
            $callback::onInstall($this->factory);

            $persist[] = $entity;
        }

        if (!empty($persist)) {
            $model->saveEntities($persist);
        }

        if ($updated || $disabled) {
            //clear the cache if plugins were updated or disabled
            $this->clearCache();
        }

        // Alert the user to the number of additions
        $this->addFlash(
            'mautic.plugin.notice.reloaded',
            array(
                '%added%'    => $added,
                '%disabled%' => $disabled,
                '%updated%'  => $updated
            )
        );

        $viewParameters = array(
            'page' => $this->factory->getSession()->get('mautic.plugin.page')
        );

        // Refresh the index contents
        return $this->postActionRedirect(
            array(
                'returnUrl'       => $this->generateUrl('mautic_plugin_index', $viewParameters),
                'viewParameters'  => $viewParameters,
                'contentTemplate' => 'MauticPluginBundle:Plugin:index',
                'passthroughVars' => array(
                    'activeLink'    => '#mautic_plugin_index',
                    'mauticContent' => 'addon'
                )
            )
        );
    }
}
