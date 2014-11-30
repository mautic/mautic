<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticAddon\MauticCrmBundle\Controller;

use Mautic\CoreBundle\Controller\CommonController;
use MauticAddon\MauticCrmBundle\Event\MapperDashboardEvent;
use MauticAddon\MauticCrmBundle\MapperEvents;

/**
 * Class DashboardController
 */
class DashboardController extends CommonController
{
    /**
     * Generates the default view
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function indexAction()
    {
        $event = new MapperDashboardEvent($this->factory->getSecurity());
        $this->factory->getDispatcher()->dispatch(MapperEvents::FETCH_ICONS, $event);
        $applications = $event->getApplications();

        return $this->delegateView(array(
            'viewParameters'  =>  array(
                'applications' => $applications,
                'total'        => count($applications)
            ),
            'contentTemplate' => 'MauticCrmBundle:Dashboard:index.html.php',
            'passthroughVars' => array(
                'activeLink'     => '#mautic_crm_dashboard_index',
                'mauticContent'  => 'dashboard'
            )
        ));
    }
}