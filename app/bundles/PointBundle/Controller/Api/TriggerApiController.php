<?php

namespace Mautic\PointBundle\Controller\Api;

use Doctrine\Persistence\ManagerRegistry;
use Mautic\ApiBundle\Controller\CommonApiController;
use Mautic\ApiBundle\Helper\EntityResultHelper;
use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\CoreBundle\Factory\ModelFactory;
use Mautic\CoreBundle\Helper\AppVersion;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\PointBundle\Entity\Trigger;
use Mautic\PointBundle\Model\TriggerEventModel;
use Mautic\PointBundle\Model\TriggerModel;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;

/**
 * @extends CommonApiController<Trigger>
 */
class TriggerApiController extends CommonApiController
{
    /**
     * @var TriggerModel|null
     */
    protected $model;

    public function __construct(
        CorePermissions $security,
        Translator $translator,
        EntityResultHelper $entityResultHelper,
        RouterInterface $router,
        FormFactoryInterface $formFactory,
        AppVersion $appVersion,
        private ?RequestStack $requestStack,
        ManagerRegistry $doctrine,
        ModelFactory $modelFactory,
        EventDispatcherInterface $dispatcher,
        CoreParametersHelper $coreParametersHelper,
        MauticFactory $factory
    ) {
        $triggerModel = $modelFactory->getModel('point.trigger');
        \assert($triggerModel instanceof TriggerModel);

        $this->model            = $triggerModel;
        $this->entityClass      = Trigger::class;
        $this->entityNameOne    = 'trigger';
        $this->entityNameMulti  = 'triggers';
        $this->serializerGroups = ['triggerDetails', 'categoryList', 'publishDetails'];

        parent::__construct($security, $translator, $entityResultHelper, $router, $formFactory, $appVersion, $requestStack, $doctrine, $modelFactory, $dispatcher, $coreParametersHelper, $factory);
    }

    protected function preSaveEntity(&$entity, $form, $parameters, $action = 'edit')
    {
        $method            = $this->requestStack->getCurrentRequest()->getMethod();
        $triggerEventModel = $this->getModel('point.triggerevent');
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

                if (!($triggerEventForm->isSubmitted() && $triggerEventForm->isValid())) {
                    $formErrors = $this->getFormErrorMessages($triggerEventForm);
                    $msg        = $this->getFormErrorMessage($formErrors);

                    return $this->returnError('Trigger events: '.$msg, Response::HTTP_BAD_REQUEST);
                }
            }

            $this->model->setEvents($entity, $parameters['events']);
        }

        // Remove events which weren't in the PUT request
        if (!$isNew && 'PUT' === $method) {
            foreach ($currentEvents as $currentEvent) {
                if (!in_array($currentEvent->getId(), $requestTriggerIds)) {
                    $entity->removeTriggerEvent($currentEvent);
                }
            }
        }
    }

    protected function createTriggerEventEntityForm($entity): FormInterface
    {
        $triggerEventModel = $this->getModel('point.triggerevent');
        \assert($triggerEventModel instanceof TriggerEventModel);

        return $triggerEventModel->createForm(
            $entity,
            $this->formFactory,
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

        if (null === $entity) {
            return $this->notFound();
        }

        $eventsToDelete = $this->requestStack->getCurrentRequest()->get('events');
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
