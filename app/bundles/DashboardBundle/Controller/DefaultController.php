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

        $popularPages = $this->factory->getModel('page.page')->getRepository()->getPopularPages();
        $popularAssets = $this->factory->getModel('asset.asset')->getRepository()->getPopularAssets();
        $popularCampaigns = $this->factory->getModel('campaign.campaign')->getRepository()->getPopularCampaigns();

        return $this->delegateView(array(
            'viewParameters'  =>  array(
                'popularPages' => $popularPages,
                'popularAssets' => $popularAssets,
                'popularCampaigns' => $popularCampaigns,
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
