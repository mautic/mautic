<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CalendarBundle\Controller;

use Mautic\CoreBundle\Controller\AjaxController as CommonAjaxController;
use Mautic\CoreBundle\Helper\InputHelper;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class AjaxController
 */
class AjaxController extends CommonAjaxController
{

    /**
     * Generates the calendar data
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function generateDataAction(Request $request)
    {
        $dates = array(
            'start_date' => $request->query->get('start'),
            'end_date'   => $request->query->get('end')
        );

        /* @type \Mautic\CalendarBundle\Model\CalendarModel $model */
        $model  = $this->factory->getModel('calendar');
        $events = $model->getCalendarEvents($dates);

        return $this->sendJsonResponse($events);
    }

}
