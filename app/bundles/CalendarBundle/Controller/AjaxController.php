<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CalendarBundle\Controller;

use Mautic\CoreBundle\Controller\AjaxController as CommonAjaxController;
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

    /**
     * Updates an event on dragging the event around the calendar
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function updateEventAction(Request $request)
    {
        $entityId   = $request->request->get('entityId');
        $source     = $request->request->get('entityType');
        $setter     = 'set' . $request->request->get('setter');
        $dateValue  = new \DateTime($request->request->get('startDate'));
        $response   = array('success' => false);

        /* @type \Mautic\CalendarBundle\Model\CalendarModel $model */
        $calendarModel  = $this->factory->getModel('calendar');
        $event          = $calendarModel->editCalendarEvent($source, $entityId);

        $model   = $event->getModel();
        $entity  = $event->getEntity();

        //not found
        if ($entity === null) {
            // TODO - it would be maybe nice to display a flash message
        } elseif (!$event->hasAccess()) {
            // TODO - it would be maybe nice to display a flash message
        } elseif ($model->isLocked($entity)) {
            // TODO - it would be maybe nice to display a flash message
        }elseif ($this->request->getMethod() == 'POST') {
            $entity->$setter($dateValue);
            $model->saveEntity($entity);
            $response['success'] = true;

            $this->request->getSession()->getFlashBag()->add(
                'notice',
                $this->get('translator')->trans('mautic.core.notice.updated', array(
                    '%name%'      => $entity->getTitle(),
                    '%menu_link%' => 'mautic_' . $source . '_index',
                    '%url%'       => $this->generateUrl('mautic_' . $source . '_action', array(
                        'objectAction' => 'edit',
                        'objectId'     => $entity->getId()
                    ))
                ), 'flashes')
            );
        }

        //render flashes
        $response['flashes'] = $this->getFlashContent();

        return $this->sendJsonResponse($response);
    }
}
