<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CalendarBundle\Controller;

use Mautic\CoreBundle\Controller\AjaxController as CommonAjaxController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class AjaxController.
 */
class AjaxController extends CommonAjaxController
{
    /**
     * Generates the calendar data.
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function generateDataAction(Request $request)
    {
        $dates = [
            'start_date' => $request->query->get('start'),
            'end_date'   => $request->query->get('end'),
        ];

        /* @type \Mautic\CalendarBundle\Model\CalendarModel $model */
        $model  = $this->getModel('calendar');
        $events = $model->getCalendarEvents($dates);

        $this->checkEventPermissions($events);

        // Can't use $this->sendJsonResponse, because it converts arrays to objects and Fullcalendar doesn't render events then.
        $response = new Response();
        $response->setContent(json_encode($events));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    /**
     * Updates an event on dragging the event around the calendar.
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function updateEventAction(Request $request)
    {
        $entityId  = $request->request->get('entityId');
        $source    = $request->request->get('entityType');
        $setter    = 'set'.$request->request->get('setter');
        $dateValue = new \DateTime($request->request->get('startDate'));
        $response  = ['success' => false];

        /* @type \Mautic\CalendarBundle\Model\CalendarModel $model */
        $calendarModel = $this->getModel('calendar');
        $event         = $calendarModel->editCalendarEvent($source, $entityId);

        $model  = $event->getModel();
        $entity = $event->getEntity();

        //not found
        if ($entity === null) {
            $this->addFlash('mautic.core.error.notfound', 'error');
        } elseif (!$event->hasAccess()) {
            $this->addFlash('mautic.core.error.accessdenied', 'error');
        } elseif ($model->isLocked($entity)) {
            $this->addFlash(
                'mautic.core.error.locked',
                [
                    '%name%'      => $entity->getTitle(),
                    '%menu_link%' => 'mautic_'.$source.'_index',
                    '%url%'       => $this->generateUrl(
                        'mautic_'.$source.'_action',
                        [
                            'objectAction' => 'edit',
                            'objectId'     => $entity->getId(),
                        ]
                    ),
                ]
            );
        } elseif ($this->request->getMethod() == 'POST') {
            $entity->$setter($dateValue);
            $model->saveEntity($entity);
            $response['success'] = true;

            $this->addFlash(
                'mautic.core.notice.updated',
                [
                    '%name%'      => $entity->getTitle(),
                    '%menu_link%' => 'mautic_'.$source.'_index',
                    '%url%'       => $this->generateUrl(
                        'mautic_'.$source.'_action',
                        [
                            'objectAction' => 'edit',
                            'objectId'     => $entity->getId(),
                        ]
                    ),
                ]
            );
        }

        //render flashes
        $response['flashes'] = $this->getFlashContent();

        return $this->sendJsonResponse($response);
    }

    /**
     * @param $events
     */
    public function checkEventPermissions(&$events)
    {
        $security     = $this->get('mautic.security');
        $modelFactory = $this->get('mautic.model.factory');

        foreach ($events as $key => $event) {
            //make sure the user has view access to the entities
            foreach ($event as $eventKey => $eventValue) {
                if (substr($eventKey, -3) === '_id') {
                    $modelName = substr($eventKey, 0, -3);
                    if ($modelFactory->hasModel($modelName)) {
                        $model = $modelFactory->getModel($modelName);
                        $base  = $model->getPermissionBase();
                        if (!$security->isGranted([$base.':viewown', $base.':viewother'], 'MATCH_ONE')) {
                            unset($events[$key]);
                        }
                    }

                    break;
                }
            }
        }
    }
}
