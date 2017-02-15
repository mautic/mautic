<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PointBundle\Controller\Api;

use Mautic\ApiBundle\Controller\CommonApiController;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;

/**
 * Class TriggerApiController.
 */
class TriggerApiController extends CommonApiController
{
    /**
     * {@inheritdoc}
     */
    public function initialize(FilterControllerEvent $event)
    {
        $this->model            = $this->getModel('point.trigger');
        $this->entityClass      = 'Mautic\PointBundle\Entity\Trigger';
        $this->entityNameOne    = 'trigger';
        $this->entityNameMulti  = 'triggers';
        $this->serializerGroups = ['triggerDetails', 'categoryList', 'publishDetails'];

        parent::initialize($event);
    }

    /**
     * {@inheritdoc}
     */
    protected function preSaveEntity(&$entity, $form, $parameters, $action = 'edit')
    {
        $method            = $this->request->getMethod();
        $triggerEventModel = $this->getModel('point.triggerEvent');
        $isNew             = false;

        // Set timestamps
        $this->model->setTimestamps($entity, true, false);

        if (!$entity->getId()) {
            $isNew = true;

            // Save the entitz first to get the ID.
            // Using the repository function to not trigger the listeners twice.
            $this->model->getRepository()->saveEntity($entity);
        }

        $requestTriggerIds = [];
        $currentEvents     = $entity->getEvents();

        // Add events from the request
        if (!empty($parameters['events']) && is_array($parameters['events'])) {
            foreach ($parameters['events'] as &$eventParams) {
                if (empty($eventParams['id'])) {
                    // Create an unique ID if not set - the following code requires one
                    $eventParams['id']  = 'new'.hash('sha1', uniqid(mt_rand()));
                    $triggerEventEntity = $triggerEventModel->getEntity();
                } else {
                    $triggerEventEntity  = $triggerEventModel->getEntity($eventParams['id']);
                    $requestTriggerIds[] = $eventParams['id'];
                }

                $triggerEventForm = $this->createTriggerEventEntityForm($triggerEventEntity);
                $triggerEventForm->submit($eventParams, 'PATCH' !== $method);

                if (!$triggerEventForm->isValid()) {
                    $formErrors = $this->getFormErrorMessages($triggerEventForm);
                    $msg        = $this->getFormErrorMessage($formErrors);

                    return $this->returnError('Trigger events: '.$msg, Codes::HTTP_BAD_REQUEST);
                }
            }

            $this->model->setEvents($entity, $parameters['events']);
        }

        // Remove events which weren't in the PUT request
        if (!$isNew && $method === 'PUT') {
            foreach ($currentEvents as $currentEvent) {
                if (!in_array($currentEvent->getId(), $requestTriggerIds)) {
                    $entity->removeTriggerEvent($currentEvent);
                }
            }
        }
    }

    /**
     * Creates the form instance.
     *
     * @param $entity
     *
     * @return Form
     */
    protected function createTriggerEventEntityForm($entity)
    {
        return $this->getModel('point.triggerEvent')->createForm(
            $entity,
            $this->get('form.factory'),
            null,
            [
                'csrf_protection'    => false,
                'allow_extra_fields' => true,
            ]
        );
    }

    /**
     * Return array of available point trigger event types.
     */
    public function getPointTriggerEventTypesAction()
    {
        if (!$this->security->isGranted([$this->permissionBase.':view', $this->permissionBase.':viewown'])) {
            return $this->accessDenied();
        }

        $eventTypesRaw = $this->model->getEvents();
        $eventTypes    = [];

        foreach ($eventTypesRaw as $key => $type) {
            $eventTypes[$key] = $type['label'];
        }

        $view = $this->view(['eventTypes' => $eventTypes]);

        return $this->handleView($view);
    }

    /**
     * Delete events from a point trigger.
     *
     * @param int $triggerId
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function deletePointTriggerEventsAction($triggerId)
    {
        if (!$this->security->isGranted([$this->permissionBase.':editown', $this->permissionBase.':editother'], 'MATCH_ONE')) {
            return $this->accessDenied();
        }

        $entity = $this->model->getEntity($triggerId);

        if ($entity === null) {
            return $this->notFound();
        }

        $eventsToDelete = $this->request->get('events');
        $currentEvents  = $entity->getEvents();

        if (!is_array($eventsToDelete)) {
            return $this->badRequest('The events attribute must be array.');
        }

        foreach ($currentEvents as $currentEvent) {
            if (in_array($currentEvent->getId(), $eventsToDelete)) {
                $entity->removeTriggerEvent($currentEvent);
            }
        }

        $view = $this->view([$this->entityNameOne => $entity]);

        return $this->handleView($view);
    }
}
