<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\DashboardBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Mautic\CoreBundle\Controller\CommonController;


/**
 * Class DefaultController
 *
 * @package Mautic\DashboardBundle\Controller
 */
class DefaultController extends CommonController
{

    /**
     * Generates the default view
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction()
    {
    	$model = $this->factory->getModel('dashboard.dashboard');
        

        //set some permissions
        $permissions = $this->factory->getSecurity()->isGranted(array(
            'dashboard:dashboards:viewown',
            'dashboard:dashboards:viewother',
            'dashboard:dashboards:create',
            'dashboard:dashboards:editown',
            'dashboard:dashboards:editother',
            'dashboard:dashboards:deleteown',
            'dashboard:dashboards:deleteother',
            'dashboard:dashboards:publishown',
            'dashboard:dashboards:publishother'
        ), "RETURN_ARRAY");

        if (!$permissions['dashboard:dashboards:viewown'] && !$permissions['dashboard:dashboards:viewother']) {
            return $this->accessDenied();
        }

        $popularPages = $this->factory->getModel('page.page')->getRepository()->getPopularPages();
        $popularAssets = $this->factory->getModel('asset.asset')->getRepository()->getPopularAssets();

        return $this->delegateView(array(
            'viewParameters'  =>  array(
                'popularPages' => $popularPages,
                'popularAssets' => $popularAssets,
                'security'    => $this->factory->getSecurity()
            ),
            'contentTemplate' => 'MauticDashboardBundle:Default:index.html.php',
            'passthroughVars' => array(
                'activeLink'     => '#mautic_dashboard_index',
                'mauticContent'  => 'dashboard'
            )
        ));
    }
}
