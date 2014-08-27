<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PointBundle\Controller;

use Mautic\CoreBundle\Controller\FormController;
use Symfony\Component\HttpFoundation\JsonResponse;

class PointController extends FormController
{

    /**
     * @param int    $page
     * @return JsonResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function indexAction($page = 1)
    {
	    /* @type \Mautic\PointBundle\Model\PointModel $model */
        $model = $this->factory->getModel('point.point');

        //set some permissions
        $permissions = $this->factory->getSecurity()->isGranted(array(
            'point:points:viewown',
            'point:points:viewother',
            'point:points:create',
            'point:points:editown',
            'point:points:editother',
            'point:points:deleteown',
            'point:points:deleteother'
        ), "RETURN_ARRAY");

        if (!$permissions['point:points:viewown'] && !$permissions['point:points:viewother']) {
            return $this->accessDenied();
        }

        //set limits
        $limit = $this->factory->getSession()->get('mautic.point.limit', $this->factory->getParameter('default_pagelimit'));
        $start = ($page === 1) ? 0 : (($page-1) * $limit);
        if ($start < 0) {
            $start = 0;
        }

        $search = $this->request->get('search', $this->factory->getSession()->get('mautic.point.filter', ''));
        $this->factory->getSession()->set('mautic.point.filter', $search);

        $filter = array('string' => $search, 'force' => array());

        if (!$permissions['point:points:viewother']) {
            $filter['force'][] =
                array('column' => 'r.createdBy', 'expr' => 'eq', 'value' => $this->factory->getUser());
        }

        $orderBy     = $this->factory->getSession()->get('mautic.point.orderby', 'e.name');
        $orderByDir  = $this->factory->getSession()->get('mautic.point.orderbydir', 'DESC');

        $points = $model->getEntities(
            array(
                'start'      => $start,
                'limit'      => $limit,
                'filter'     => $filter,
                'orderBy'    => $orderBy,
                'orderByDir' => $orderByDir
            ));

        $count = count($points);
        if ($count && $count < ($start + 1)) {
            //the number of entities are now less then the current page so redirect to the last page
            if ($count === 1) {
                $lastPage = 1;
            } else {
                $lastPage = (floor($limit / $count)) ? : 1;
            }
            $this->factory->getSession()->set('mautic.point.point', $lastPage);
            $returnUrl   = $this->generateUrl('mautic_point_index', array('page' => $lastPage));

            return $this->postActionRedirect(array(
                'returnUrl'       => $returnUrl,
                'viewParameters'  => array('page' => $lastPage),
                'contentTemplate' => 'MauticPointBundle:Point:index',
                'passthroughVars' => array(
                    'activeLink'    => '#mautic_point_index',
                    'mauticContent' => 'point'
                )
            ));
        }

        //set what page currently on so that we can return here after form submission/cancellation
        $this->factory->getSession()->set('mautic.point.page', $page);

        $tmpl = $this->request->isXmlHttpRequest() ? $this->request->get('tmpl', 'index') : 'index';

        return $this->delegateView(array(
            'viewParameters'  =>  array(
                'searchValue' => $search,
                'items'       => $points,
                'page'        => $page,
                'limit'       => $limit,
                'permissions' => $permissions,
                'model'       => $model,
                'tmpl'        => $tmpl,
                'security'    => $this->factory->getSecurity()
            ),
            'contentTemplate' => 'MauticPointBundle:Point:list.html.php',
            'passthroughVars' => array(
                'activeLink'     => '#mautic_point_index',
                'mauticContent'  => 'page',
                'route'          => $this->generateUrl('mautic_point_index', array('page' => $page)),
                'replaceContent' => ($tmpl == 'list') ? 'true' : 'false'
            )
        ));
    }
}
