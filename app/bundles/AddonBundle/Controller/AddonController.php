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
    public function indexAction($page = 1)
    {
	    /* @type \Mautic\AddonBundle\Model\IntegrationModel $model */
        $model = $this->factory->getModel('addon');

        if (!$this->factory->getSecurity()->isGranted('addon:addons:manage')) {
            return $this->accessDenied();
        }

        if ($this->request->getMethod() == 'POST') {
            $this->setTableOrder();
        }

        //set limits
        $limit = $this->factory->getSession()->get('mautic.addon.limit', $this->factory->getParameter('default_pagelimit'));
        $start = ($page === 1) ? 0 : (($page - 1) * $limit);
        if ($start < 0) {
            $start = 0;
        }

        $search = $this->request->get('search', $this->factory->getSession()->get('mautic.addon.filter', ''));
        $this->factory->getSession()->set('mautic.addon.filter', $search);

        $filter = array('string' => $search, 'force' => array());

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
            $lastPage = ($count === 1) ? 1 : (floor($limit / $count)) ?: 1;
            $this->factory->getSession()->set('mautic.addon.page', $lastPage);
            $returnUrl   = $this->generateUrl('mautic_integration_index', array('page' => $lastPage));

            return $this->postActionRedirect(array(
                'returnUrl'       => $returnUrl,
                'viewParameters'  => array('page' => $lastPage),
                'contentTemplate' => 'MauticAddonBundle:Addon:index',
                'passthroughVars' => array(
                    'activeLink'    => '#mautic_integration_index',
                    'mauticContent' => 'integration'
                )
            ));
        }

        //set what page currently on so that we can return here after form submission/cancellation
        $this->factory->getSession()->set('mautic.addon.page', $page);

        $tmpl = $this->request->isXmlHttpRequest() ? $this->request->get('tmpl', 'index') : 'index';

        return $this->delegateView(array(
            'viewParameters'  =>  array(
                'searchValue' => $search,
                'items'       => $addons,
                'page'        => $page,
                'limit'       => $limit,
                'model'       => $model,
                'tmpl'        => $tmpl
            ),
            'contentTemplate' => 'MauticAddonBundle:Addon:list.html.php',
            'passthroughVars' => array(
                'activeLink'     => '#mautic_integration_index',
                'mauticContent'  => 'integration',
                'route'          => $this->generateUrl('mautic_integration_index', array('page' => $page))
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
    protected function reloadAction($objectId)
    {
        if (!$this->factory->getSecurity()->isGranted('addon:addons:manage')) {
            return $this->accessDenied();
        }

        /** @var \Mautic\AddonBundle\Model\IntegrationModel $model */
        $model  = $this->factory->getModel('addon');
        $repo   = $model->getRepository();
        $addons = $this->factory->getParameter('addon.bundles');
        $added  = 0;

        foreach ($addons as $addon) {
            // If we don't find the bundle, we need to register it
            if (!$repo->findByBundle($addon['bundle'])) {
                $added++;
                $entity = new Addon();
                $entity->setBundle($addon['bundle']);
                $entity->setIsEnabled(false);
                $entity->setName($addon['base']);
                $model->saveEntity($entity);
            }
        }

        // Alert the user to the number of additions
        $this->request->getSession()->getFlashBag()->add(
            'notice',
            $this->get('translator')->trans('mautic.addon.notice.added', array('%added%' => $added), 'flashes')
        );

        $viewParameters = array(
            'page' => $this->factory->getSession()->get('mautic.addon.page')
        );

        // Refresh the index contents
        return $this->postActionRedirect(array(
            'returnUrl'       => $this->generateUrl('mautic_integration_index', $viewParameters),
            'viewParameters'  => $viewParameters,
            'contentTemplate' => 'MauticAddonBundle:Addon:index',
            'passthroughVars' => array(
                'activeLink'    => '#mautic_integration_index',
                'mauticContent' => 'integration'
            )
        ));
    }
}
