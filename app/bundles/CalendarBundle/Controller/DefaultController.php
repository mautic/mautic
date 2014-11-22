<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CalendarBundle\Controller;

use Mautic\CoreBundle\Controller\CommonController;

/**
 * Class DefaultController
 */
class DefaultController extends CommonController
{

    /**
     * Generates the default view
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function indexAction()
    {
        return $this->delegateView(array(
            'contentTemplate' => 'MauticCalendarBundle:Default:index.html.php',
            'passthroughVars' => array(
                'activeLink'     => '#mautic_calendar_index',
                'mauticContent'  => 'calendar',
                'route'          => $this->generateUrl('mautic_calendar_index')
            )
        ));
    }
}
