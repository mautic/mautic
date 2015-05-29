<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\AddonBundle\Controller;

use Mautic\CoreBundle\Controller\FormController;
use Mautic\AddonBundle\Entity\Addon;

/**
 * Class AddonController
 */
class AddonController extends FormController
{
    /**
     * @param int $page
     */
    public function indexAction ($page = 1)
    {
        /* @type \Mautic\AddonBundle\Model\AddonModel $model */
        $model = $this->factory->getModel('addon');

        if (!$this->factory->getSecurity()->isGranted('addon:addons:manage')) {
            return $this->accessDenied();
        }

        if ($this->request->getMethod() == 'POST') {
            $this->setListFilters();
        }

        //set limits
        $limit = $this->factory->getSession()->get('mautic.addon.limit', $this->factory->getParameter('default_pagelimit'));
        $start = ($page === 1) ? 0 : (($page - 1) * $limit);
        if ($start < 0) {
            $start = 0;
        }

        $search = $this->request->get('search', $this->factory->getSession()->get('mautic.addon.filter', ''));
        $this->factory->getSession()->set('mautic.addon.filter', $search);

        $filter = array('string' => $search, 'force' => array(
            array(
                'column' => 'i.isMissing',
                'expr'   => 'eq',
                'value'  => false
            )
        ));

        $orderBy    = $this->factory->getSession()->get('mautic.addon.orderby', 'i.name');
        $orderByDir = $this->factory->getSession()->get('mautic.addon.orderbydir', 'DESC');

        $addons = $model->getEntities(
            array(
                'start'      => $start,
                'limit'      => $limit,
                'filter'     => $filter,
                'orderBy'    => $orderBy,
                'orderByDir' => $orderByDir
            )
        );

        $count = count($addons);
        if ($count && $count < ($start + 1)) {
            //the number of entities are now less then the current page so redirect to the last page
            $lastPage = ($count === 1) ? 1 : (ceil($count / $limit)) ?: 1;
            $this->factory->getSession()->set('mautic.addon.page', $lastPage);
            $returnUrl = $this->generateUrl('mautic_addon_index', array('page' => $lastPage));

            return $this->postActionRedirect(array(
                'returnUrl'       => $returnUrl,
                'viewParameters'  => array('page' => $lastPage),
                'contentTemplate' => 'MauticAddonBundle:Addon:index',
                'passthroughVars' => array(
                    'activeLink'    => '#mautic_addon_index',
                    'mauticContent' => 'addon'
                )
            ));
        }

        //set what page currently on so that we can return here after form submission/cancellation
        $this->factory->getSession()->set('mautic.addon.page', $page);

        $tmpl = $this->request->isXmlHttpRequest() ? $this->request->get('tmpl', 'index') : 'index';

        return $this->delegateView(array(
            'viewParameters'  => array(
                'searchValue'       => $search,
                'items'             => $addons,
                'page'              => $page,
                'limit'             => $limit,
                'model'             => $model,
                'tmpl'              => $tmpl,
                'integrationHelper' => $this->factory->getHelper('integration')
            ),
            'contentTemplate' => 'MauticAddonBundle:Addon:list.html.php',
            'passthroughVars' => array(
                'activeLink'    => '#mautic_addon_index',
                'mauticContent' => 'addon',
                'route'         => $this->generateUrl('mautic_addon_index', array('page' => $page))
            )
        ));
    }

    /**
     * Scans the addon bundles directly and loads bundles which are not registered to the database
     *
     * @param int $objectId Unused in this action
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    protected function reloadAction ($objectId)
    {
        if (!$this->factory->getSecurity()->isGranted('addon:addons:manage')) {
            return $this->accessDenied();
        }

        /** @var \Mautic\AddonBundle\Model\AddonModel $model */
        $model  = $this->factory->getModel('addon');
        /** @var \Mautic\AddonBundle\Entity\AddonRepository $repo */
        $repo   = $model->getRepository();
        $addons = $this->factory->getParameter('addon.bundles');
        $added  = $disabled = $updated = 0;

        $installedAddons = $repo->getInstalled();

        $persist = array();
        foreach ($installedAddons as $bundle => $addon) {
            $persistUpdate = false;
            if (!isset($addons[$bundle])) {
                if (!$addon->getIsMissing()) {
                    //files are no longer found
                    $addon->setIsEnabled(false);
                    $addon->setIsMissing(true);
                    $disabled++;
                }
            } else {
                if ($addon->getIsMissing()) {
                    //was lost but now is found
                    $addon->setIsMissing(false);
                    $persistUpdate = true;
                }

                $file = $addons[$bundle]['directory'] . '/Config/config.php';

                //update details of the bundle
                if (file_exists($file)) {
                    $details = include $file;

                    //compare versions to see if an update is necessary
                    $version = isset($details['version']) ? $details['version'] : '';
                    if (!empty($version) && version_compare($addon->getVersion(), $version) == -1) {
                        $updated++;

                        //call the update callback
                        $callback = $addons[$bundle]['bundleClass'];
                        $callback::onUpdate($addon, $this->factory);
                        $persistUpdate = true;
                    }

                    $addon->setVersion($version);

                    $addon->setName(
                        isset($details['name']) ? $details['name'] : $addons[$bundle]['base']
                    );

                    if (isset($details['description'])) {
                        $addon->setDescription($details['description']);
                    }

                    if (isset($details['author'])) {
                        $addon->setAuthor($details['author']);
                    }
                }

                unset($addons[$bundle]);
            }
            if ($persistUpdate) {
                $persist[] = $addon;
            }
        }

        //rest are new
        foreach ($addons as $addon) {
            $added++;
            $entity = new Addon();
            $entity->setBundle($addon['bundle']);
            $entity->setIsEnabled(false);

            $file = $addon['directory'] . '/Config/config.php';

            //update details of the bundle
            if (file_exists($file)) {
                $details = include $file;

                if (isset($details['version'])) {
                    $entity->setVersion($details['version']);
                };

                $entity->setName(
                    isset($details['name']) ? $details['name'] : $addon['base']
                );

                if (isset($details['description'])) {
                    $entity->setDescription($details['description']);
                }

                if (isset($details['author'])) {
                    $entity->setAuthor($details['author']);
                }
            }

            //call the install callback
            $callback = $addon['bundleClass'];
            $callback::onInstall($this->factory);

            $persist[] = $entity;
        }

        if (!empty($persist)) {
            $model->saveEntities($persist);
        }

        if ($updated || $disabled) {
            //clear the cache if addons were updated or disabled
            $this->clearCache();
        }

        // Alert the user to the number of additions
        $this->addFlash('mautic.addon.notice.reloaded', array(
            '%added%'    => $added,
            '%disabled%' => $disabled,
            '%updated%'  => $updated
        ));

        $viewParameters = array(
            'page' => $this->factory->getSession()->get('mautic.addon.page')
        );

        // Refresh the index contents
        return $this->postActionRedirect(array(
            'returnUrl'       => $this->generateUrl('mautic_addon_index', $viewParameters),
            'viewParameters'  => $viewParameters,
            'contentTemplate' => 'MauticAddonBundle:Addon:index',
            'passthroughVars' => array(
                'activeLink'    => '#mautic_addon_index',
                'mauticContent' => 'addon'
            )
        ));
    }
}
