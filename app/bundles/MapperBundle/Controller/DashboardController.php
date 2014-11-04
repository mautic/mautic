<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\MapperBundle\Controller;

use Mautic\CoreBundle\Controller\CommonController;
use Mautic\MapperBundle\Event\MapperDashboardEvent;
use Mautic\MapperBundle\MapperEvents;

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

        print_r($applications);
        die();

        return $this->delegateView(array(
            'viewParameters'  =>  array(
                'applications' => $applications
            ),
            'contentTemplate' => 'MauticMapperBundle:Dashboard:index.html.php',
            'passthroughVars' => array(
                'activeLink'     => '#mautic_mapper_dashboard_index',
                'mauticContent'  => 'dashboard'
            )
        ));
    }
}