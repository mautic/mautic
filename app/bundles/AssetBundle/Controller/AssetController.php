<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\AssetBundle\Controller;

use Mautic\CoreBundle\Controller\FormController;
use Mautic\CoreBundle\Helper\InputHelper;
use Symfony\Component\HttpFoundation\JsonResponse;

class AssetController extends FormController
{

    /**
     * @param int    $asset
     * @return JsonResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function indexAction($asset = 1)
    {

        $model = $this->factory->getModel('asset.asset');

        //set some permissions
        $permissions = $this->factory->getSecurity()->isGranted(array(
            'asset:assets:viewown',
            'asset:assets:viewother',
            'asset:assets:create',
            'asset:assets:editown',
            'asset:assets:editother',
            'asset:assets:deleteown',
            'asset:assets:deleteother',
            'asset:assets:publishown',
            'asset:assets:publishother'
        ), "RETURN_ARRAY");

        if (!$permissions['asset:assets:viewown'] && !$permissions['asset:assets:viewother']) {
            return $this->accessDenied();
        }

        //set limits
        $limit = $this->factory->getSession()->get('mautic.asset.limit', $this->factory->getParameter('default_assetlimit'));
        $start = ($asset === 1) ? 0 : (($asset-1) * $limit);
        if ($start < 0) {
            $start = 0;
        }

        $search = $this->request->get('search', $this->factory->getSession()->get('mautic.asset.filter', ''));
        $this->factory->getSession()->set('mautic.asset.filter', $search);

        $filter = array('string' => $search, 'force' => array());

        if (!$permissions['asset:assets:viewother']) {
            $filter['force'][] =
                array('column' => 'p.createdBy', 'expr' => 'eq', 'value' => $this->factory->getUser());
        }

        $translator = $this->get('translator');
        //do not list variants in the main list
        $filter['force'][] = array('column' => 'p.variantParent', 'expr' => 'isNull');

        $langSearchCommand = $translator->trans('mautic.asset.asset.searchcommand.lang');
        if (strpos($search, "{$langSearchCommand}:") === false) {
            $filter['force'][] = array('column' => 'p.translationParent', 'expr' => 'isNull');
        }

        $orderBy     = $this->factory->getSession()->get('mautic.asset.orderby', 'p.title');
        $orderByDir  = $this->factory->getSession()->get('mautic.asset.orderbydir', 'DESC');

        $assets = $model->getEntities(
            array(
                'start'      => $start,
                'limit'      => $limit,
                'filter'     => $filter,
                'orderBy'    => $orderBy,
                'orderByDir' => $orderByDir
            ));

        $count = count($assets);
        if ($count && $count < ($start + 1)) {
            //the number of entities are now less then the current asset so redirect to the last asset
            if ($count === 1) {
                $lastAsset = 1;
            } else {
                $lastAsset = (floor($limit / $count)) ? : 1;
            }
            $this->factory->getSession()->set('mautic.asset.asset', $lastasset);
            $returnUrl   = $this->generateUrl('mautic_asset_index', array('asset' => $lastAsset));

            return $this->postActionRedirect(array(
                'returnUrl'       => $returnUrl,
                'viewParameters'  => array('asset' => $lastAsset),
                'contentTemplate' => 'MauticAssetBundle:Asset:index',
                'passthroughVars' => array(
                    'activeLink'    => '#mautic_asset_index',
                    'mauticContent' => 'asset'
                )
            ));
        }

        //set what asset currently on so that we can return here after form submission/cancellation
        $this->factory->getSession()->set('mautic.asset.asset', $asset);

        $tmpl = $this->request->isXmlHttpRequest() ? $this->request->get('tmpl', 'index') : 'index';

        //retrieve a list of categories
        $categories = $this->factory->getModel('asset.asset')->getLookupResults('category', '', 0);

        return $this->delegateView(array(
            'viewParameters'  =>  array(
                'searchValue' => $search,
                'items'       => $assets,
                'categories'  => $categories,
                'asset'        => $asset,
                'limit'       => $limit,
                'permissions' => $permissions,
                'model'       => $model,
                'tmpl'        => $tmpl,
                'security'    => $this->factory->getSecurity()
            ),
            'contentTemplate' => 'MauticAssetBundle:Asset:list.html.php',
            'passthroughVars' => array(
                'activeLink'     => '#mautic_asset_index',
                'mauticContent'  => 'asset',
                'route'          => $this->generateUrl('mautic_asset_index', array('asset' => $asset)),
                'replaceContent' => ($tmpl == 'list') ? 'true' : 'false'
            )
        ));
    }
}