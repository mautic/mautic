<?php

namespace Mautic\PointBundle\Controller;

use Mautic\CoreBundle\Controller\FormController as CommonFormController;
use Mautic\PointBundle\Entity\TriggerEvent;
use Mautic\PointBundle\Form\Type\TriggerEventType;
use Mautic\PointBundle\Model\TriggerModel;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class TriggerEventController extends CommonFormController
{
    /**
     * Generates new form and processes post data.
     *
     * @return Response
     */
    public function newAction(Request $request)
    {
        $success = 0;
        $valid   = $cancelled   = false;
        $method  = $request->getMethod();
        $session = $request->getSession();

        if ('POST' == $method) {
            $triggerEvent = $request->request->all()['pointtriggerevent'] ?? [];
            $eventType    = $triggerEvent['type'];
            $triggerId    = $triggerEvent['triggerId'];
        } else {
            $eventType = $request->query->get('type');
            $triggerId = $request->query->get('triggerId');

            $triggerEvent = [
                'type'      => $eventType,
                'triggerId' => $triggerId,
            ];
        }

        // ajax only for form fields
        if (!$eventType ||
            !$request->isXmlHttpRequest() ||
            !$this->security->isGranted([
                'point:triggers:edit',
                'point:triggers:create',
            ], 'MATCH_ONE')
        ) {
            return $this->modalAccessDenied();
        }

        // fire the builder event
        /** @var TriggerModel $pointTriggerModel */
        $pointTriggerModel = $this->getModel('point.trigger');
        \assert($pointTriggerModel instanceof TriggerModel);
        $events = $pointTriggerModel->getEvents();
        $form   = $this->formFactory->create(TriggerEventType::class, $triggerEvent, [
            'action'   => $this->generateUrl('mautic_pointtriggerevent_action', ['objectAction' => 'new']),
            'settings' => $events[$eventType],
        ]);
        $form->get('triggerId')->setData($triggerId);
        $triggerEvent['settings'] = $events[$eventType];

        // Check for a submitted form and process it
        if ('POST' == $method) {
            if (!$cancelled = $this->isFormCancelled($form)) {
                if ($valid = $this->isFormValid($form)) {
                    $success = 1;

                    // form is valid so process the data
                    $keyId = 'new'.hash('sha1', uniqid(mt_rand()));

                    // save the properties to session
                    $actions            = $session->get('mautic.point.'.$triggerId.'.triggerevents.modified');
                    $formData           = $form->getData();
                    $triggerEvent       = array_merge($triggerEvent, $formData);
                    $triggerEvent['id'] = $keyId;
                    if (empty($triggerEvent['name'])) {
                        // set it to the event default
                        $triggerEvent['name'] = $this->translator->trans($triggerEvent['settings']['label']);
                    }
                    $actions[$keyId] = $triggerEvent;
                    $session->set('mautic.point.'.$triggerId.'.triggerevents.modified', $actions);
                }
            }
        }

        $viewParams = ['type' => $eventType];
        if ($cancelled || $valid) {
            $closeModal = true;
        } else {
            if (isset($triggerEvent['settings']['formTheme'])) {
                $viewParams['formTheme'] = $triggerEvent['settings']['formTheme'];
            }

            $closeModal                = false;
            $viewParams['form']        = $form->createView();
            $header                    = $triggerEvent['settings']['label'];
            $viewParams['eventHeader'] = $this->translator->trans($header);
        }

        $passthroughVars = [
            'mauticContent' => 'pointTriggerEvent',
            'success'       => $success,
            'route'         => false,
        ];

        if (!empty($keyId)) {
            // prevent undefined errors
            $entity       = new TriggerEvent();
            $blank        = $entity->convertToArray();
            $triggerEvent = array_merge($blank, $triggerEvent);

            $template = (empty($triggerEvent['settings']['template'])) ? '@MauticPoint/Event/generic.html.twig'
                : $triggerEvent['settings']['template'];

            $passthroughVars['eventId']   = $keyId;
            $passthroughVars['eventHtml'] = $this->renderView($template, [
                'event'     => $triggerEvent,
                'id'        => $keyId,
                'sessionId' => $triggerId,
            ]);
        }

        if ($closeModal) {
            // just close the modal
            $passthroughVars['closeModal'] = 1;

            return new JsonResponse($passthroughVars);
        }

        return $this->ajaxAction($request, [
            'contentTemplate' => '@MauticPoint/Event/form.html.twig',
            'viewParameters'  => $viewParams,
            'passthroughVars' => $passthroughVars,
        ]);
    }

    /**
     * Generates edit form and processes post data.
     *
     * @param int $objectId
     *
     * @return Response
     */
    public function editAction(Request $request, $objectId)
    {
        $session      = $request->getSession();
        $method       = $request->getMethod();
        $triggerEvent = $request->request->get('pointtriggerevent') ?? [];
        $triggerId    = 'POST' === $method ? ($triggerEvent['triggerId'] ?? '') : $request->query->get('triggerId');
        $events       = $session->get('mautic.point.'.$triggerId.'.triggerevents.modified', []);
        $success      = 0;
        $valid        = $cancelled = false;
        $triggerEvent = array_key_exists($objectId, $events) ? $events[$objectId] : null;

        if (null !== $triggerEvent) {
            $eventType         = $triggerEvent['type'];
            $pointTriggerModel = $this->getModel('point.trigger');
            \assert($pointTriggerModel instanceof TriggerModel);
            $events                   = $pointTriggerModel->getEvents();
            $triggerEvent['settings'] = $events[$eventType];

            // ajax only for form fields
            if (!$eventType ||
                !$request->isXmlHttpRequest() ||
                !$this->security->isGranted([
                    'point:triggers:edit',
                    'point:triggers:create',
                ], 'MATCH_ONE')
            ) {
                return $this->modalAccessDenied();
            }

            $form = $this->formFactory->create(TriggerEventType::class, $triggerEvent, [
                'action'   => $this->generateUrl('mautic_pointtriggerevent_action', ['objectAction' => 'edit', 'objectId' => $objectId]),
                'settings' => $triggerEvent['settings'],
            ]);
            $form->get('triggerId')->setData($triggerId);
            // Check for a submitted form and process it
            if ('POST' == $method) {
                if (!$cancelled = $this->isFormCancelled($form)) {
                    if ($valid = $this->isFormValid($form)) {
                        $success = 1;

                        // form is valid so process the data

                        // save the properties to session
                        $session  = $request->getSession();
                        $events   = $session->get('mautic.point.'.$triggerId.'.triggerevents.modified');
                        $formData = $form->getData();
                        // overwrite with updated data
                        $triggerEvent = array_merge($events[$objectId], $formData);
                        if (empty($triggerEvent['name'])) {
                            // set it to the event default
                            $triggerEvent['name'] = $this->translator->trans($triggerEvent['settings']['label']);
                        }
                        $events[$objectId] = $triggerEvent;
                        $session->set('mautic.point.'.$triggerId.'.triggerevents.modified', $events);

                        // generate HTML for the field
                        $keyId = $objectId;
                    }
                }
            }

            $viewParams = ['type' => $eventType];
            if ($cancelled || $valid) {
                $closeModal = true;
            } else {
                if (isset($triggerEvent['settings']['formTheme'])) {
                    $viewParams['formTheme'] = $triggerEvent['settings']['formTheme'];
                }

                $closeModal                = false;
                $viewParams['form']        = $form->createView();
                $viewParams['eventHeader'] = $this->translator->trans($triggerEvent['settings']['label']);
            }

            $passthroughVars = [
                'mauticContent' => 'pointTriggerEvent',
                'success'       => $success,
                'route'         => false,
            ];

            if (!empty($keyId)) {
                $passthroughVars['eventId'] = $keyId;

                // prevent undefined errors
                $entity       = new TriggerEvent();
                $blank        = $entity->convertToArray();
                $triggerEvent = array_merge($blank, $triggerEvent);
                $template     = (empty($triggerEvent['settings']['template'])) ? '@MauticPoint/Event/generic.html.twig'
                    : $triggerEvent['settings']['template'];

                $passthroughVars['eventId']   = $keyId;
                $passthroughVars['eventHtml'] = $this->renderView($template, [
                    'event'     => $triggerEvent,
                    'id'        => $keyId,
                    'sessionId' => $triggerId,
                ]);
            }

            if ($closeModal) {
                // just close the modal
                $passthroughVars['closeModal'] = 1;

                return new JsonResponse($passthroughVars);
            }

            return $this->ajaxAction($request, [
                'contentTemplate' => '@MauticPoint/Event/form.html.twig',
                'viewParameters'  => $viewParams,
                'passthroughVars' => $passthroughVars,
            ]);
        }

        return new JsonResponse(['success' => 0]);
    }

    /**
     * Deletes the entity.
     *
     * @param int $objectId
     *
     * @return JsonResponse
     */
    public function deleteAction(Request $request, $objectId)
    {
        $session   = $request->getSession();
        $triggerId = $request->get('triggerId');
        $events    = $session->get('mautic.point.'.$triggerId.'.triggerevents.modified', []);
        $delete    = $session->get('mautic.point.'.$triggerId.'.triggerevents.deleted', []);

        // ajax only for form fields
        if (!$request->isXmlHttpRequest() ||
            !$this->security->isGranted([
                'point:triggers:edit',
                'point:triggers:create',
            ], 'MATCH_ONE')
        ) {
            return $this->accessDenied();
        }

        $triggerEvent = (array_key_exists($objectId, $events)) ? $events[$objectId] : null;

        if ('POST' == $request->getMethod() && null !== $triggerEvent) {
            // add the field to the delete list
            if (!in_array($objectId, $delete)) {
                $delete[] = $objectId;
                $session->set('mautic.point.'.$triggerId.'.triggerevents.deleted', $delete);
            }

            $template = (empty($triggerEvent['settings']['template'])) ? '@MauticPoint/Event/generic.html.twig'
                : $triggerEvent['settings']['template'];

            // prevent undefined errors
            $entity       = new TriggerEvent();
            $blank        = $entity->convertToArray();
            $triggerEvent = array_merge($blank, $triggerEvent);

            $dataArray = [
                'mauticContent' => 'pointTriggerEvent',
                'success'       => 1,
                'target'        => '#triggerEvent'.$objectId,
                'route'         => false,
                'eventId'       => $objectId,
                'eventHtml'     => $this->renderView($template, [
                    'event'     => $triggerEvent,
                    'id'        => $objectId,
                    'deleted'   => true,
                    'sessionId' => $triggerId,
                ]),
            ];
        } else {
            $dataArray = ['success' => 0];
        }

        return new JsonResponse($dataArray);
    }

    /**
     * Undeletes the entity.
     *
     * @param int $objectId
     *
     * @return JsonResponse
     */
    public function undeleteAction(Request $request, $objectId)
    {
        $session   = $request->getSession();
        $triggerId = $request->get('triggerId');
        $events    = $session->get('mautic.point.'.$triggerId.'.triggerevents.modified', []);
        $delete    = $session->get('mautic.point.'.$triggerId.'.triggerevents.deleted', []);

        // ajax only for form fields
        if (!$request->isXmlHttpRequest() ||
            !$this->security->isGranted([
                'point:triggers:edit',
                'point:triggers:create',
            ], 'MATCH_ONE')
        ) {
            return $this->accessDenied();
        }

        $triggerEvent = (array_key_exists($objectId, $events)) ? $events[$objectId] : null;

        if ('POST' === $request->getMethod() && null !== $triggerEvent) {
            // add the field to the delete list
            if (in_array($objectId, $delete)) {
                $key = array_search($objectId, $delete);
                unset($delete[$key]);
                $session->set('mautic.point.'.$triggerId.'.triggerevents.deleted', $delete);
            }

            $template = (empty($triggerEvent['settings']['template'])) ? '@MauticPoint/Event/generic.html.twig'
                : $triggerEvent['settings']['template'];

            // prevent undefined errors
            $entity       = new TriggerEvent();
            $blank        = $entity->convertToArray();
            $triggerEvent = array_merge($blank, $triggerEvent);

            $dataArray = [
                'mauticContent' => 'pointTriggerEvent',
                'success'       => 1,
                'target'        => '#triggerEvent'.$objectId,
                'route'         => false,
                'eventId'       => $objectId,
                'eventHtml'     => $this->renderView($template, [
                    'event'     => $triggerEvent,
                    'id'        => $objectId,
                    'deleted'   => false,
                    'triggerId' => $triggerId,
                ]),
            ];
        } else {
            $dataArray = ['success' => 0];
        }

        return new JsonResponse($dataArray);
    }
}
