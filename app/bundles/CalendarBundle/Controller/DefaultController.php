<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CalendarBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Mautic\CoreBundle\Controller\CommonController;
use Symfony\Component\Intl\Intl;

/**
 * Class DefaultController
 *
 * @package Mautic\CalendarBundle\Controller
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
    	$model = $this->factory->getModel('calendar.calendar');

        $search = $this->request->get('search', $this->factory->getSession()->get('mautic.asset.filter', ''));
        $this->factory->getSession()->set('mautic.asset.filter', $search);

        return $this->delegateView(array(
            'viewParameters'  =>  array(
                'searchValue' => $search
            ),
            'contentTemplate' => 'MauticCalendarBundle:Default:index.html.php',
            'passthroughVars' => array(
                'activeLink'     => '#mautic_calendar_index',
                'mauticContent'  => 'calendar',
                'route'          => $this->generateUrl('mautic_calendar_index')
            )
        ));
    }
}
