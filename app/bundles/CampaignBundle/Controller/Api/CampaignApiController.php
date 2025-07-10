<?php

namespace Mautic\CampaignBundle\Controller\Api;

use Doctrine\Persistence\ManagerRegistry;
use Mautic\ApiBundle\Controller\CommonApiController;
use Mautic\ApiBundle\Helper\EntityResultHelper;
use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\Membership\MembershipManager;
use Mautic\CampaignBundle\Model\CampaignModel;
use Mautic\CampaignBundle\Model\EventModel;
use Mautic\CoreBundle\Factory\ModelFactory;
use Mautic\CoreBundle\Helper\AppVersion;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\InputHelper;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\LeadBundle\Controller\LeadAccessTrait;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @extends CommonApiController<Campaign>
 */
class CampaignApiController extends CommonApiController
{
    use LeadAccessTrait;

    /**
     * @var CampaignModel|null
     */
    protected $model;

    public function __construct(
        CorePermissions $security,
        Translator $translator,
        EntityResultHelper $entityResultHelper,
        RouterInterface $router,
        FormFactoryInterface $formFactory,
        AppVersion $appVersion,
        private RequestStack $requestStack,
        private MembershipManager $membershipManager,
        ManagerRegistry $doctrine,
        ModelFactory $modelFactory,
        EventDispatcherInterface $dispatcher,
        CoreParametersHelper $coreParametersHelper,
        private ValidatorInterface $validator,
        private EventModel $eventModel,
    ) {
        $campaignModel = $modelFactory->getModel('campaign');
        \assert($campaignModel instanceof CampaignModel);

        $this->model             = $campaignModel;
        $this->entityClass       = Campaign::class;
        $this->entityNameOne     = 'campaign';
        $this->entityNameMulti   = 'campaigns';
        $this->permissionBase    = 'campaign:campaigns';
        $this->serializerGroups  = ['campaignDetails', 'campaignEventDetails', 'categoryList', 'publishDetails', 'leadListList', 'formList'];

        parent::__construct($security, $translator, $entityResultHelper, $router, $formFactory, $appVersion, $requestStack, $doctrine, $modelFactory, $dispatcher, $coreParametersHelper);
    }

    /**
     * Adds a lead to a campaign.
     *
     * @param int $id     Campaign ID
     * @param int $leadId Lead ID
     *
     * @return Response
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function addLeadAction($id, $leadId)
    {
        $entity = $this->model->getEntity($id);
        if (null !== $entity) {
            $leadModel = $this->getModel('lead');
            $lead      = $leadModel->getEntity($leadId);

            if (null == $lead) {
                return $this->notFound();
            } elseif (!$this->security->hasEntityAccess('lead:leads:editown', 'lead:leads:editother', $lead->getOwner())) {
                return $this->accessDenied();
            }

            $this->membershipManager->addContact($lead, $entity);

            $view = $this->view(['success' => 1], Response::HTTP_OK);

            return $this->handleView($view);
        }

        return $this->notFound();
    }

    /**
     * Removes given lead from a campaign.
     *
     * @param int $id     Campaign ID
     * @param int $leadId Lead ID
     *
     * @return Response
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function removeLeadAction($id, $leadId)
    {
        $entity = $this->model->getEntity($id);
        if (null !== $entity) {
            $lead = $this->checkLeadAccess($leadId, 'edit');
            if ($lead instanceof Response) {
                return $lead;
            }

            $this->membershipManager->removeContact($lead, $entity);

            $view = $this->view(['success' => 1], Response::HTTP_OK);

            return $this->handleView($view);
        }

        return $this->notFound();
    }

    /**
     * @param Campaign &$entity
     * @param string   $action
     */
    protected function preSaveEntity(&$entity, $form, $parameters, $action = 'edit')
    {
        $method = $this->requestStack->getCurrentRequest()->getMethod();

        if ('POST' === $method || 'PUT' === $method) {
            if (empty($parameters['events'])) {
                $msg = $this->translator->trans('mautic.campaign.form.events.notempty', [], 'validators');

                return $this->returnError($msg, Response::HTTP_BAD_REQUEST);
            } elseif (empty($parameters['lists']) && empty($parameters['forms'])) {
                $msg = $this->translator->trans('mautic.campaign.form.sources.notempty', [], 'validators');

                return $this->returnError($msg, Response::HTTP_BAD_REQUEST);
            }
        }

        $deletedSources = ['lists' => [], 'forms' => []];
        $deletedEvents  = [];
        $currentSources = [
            'lists' => isset($parameters['lists']) ? $this->modifyCampaignEventArray($parameters['lists']) : [],
            'forms' => isset($parameters['forms']) ? $this->modifyCampaignEventArray($parameters['forms']) : [],
        ];

        // delete events and sources which does not exist in the PUT request
        if ('PUT' === $method) {
            $requestEventIds   = [];
            $requestSegmentIds = [];
            $requestFormIds    = [];

            foreach ($parameters['events'] as $key => $requestEvent) {
                if (!isset($requestEvent['id'])) {
                    return $this->returnError('$campaign[events]['.$key.']["id"] is missing', Response::HTTP_BAD_REQUEST);
                }
                $requestEventIds[] = $requestEvent['id'];
            }

            foreach ($entity->getEvents() as $currentEvent) {
                if (!in_array($currentEvent->getId(), $requestEventIds)) {
                    $deletedEvents[] = $currentEvent->getId();
                }
            }

            if (isset($parameters['lists'])) {
                foreach ($parameters['lists'] as $requestSegment) {
                    if (!isset($requestSegment['id'])) {
                        return $this->returnError('$campaign[lists]['.$key.']["id"] is missing', Response::HTTP_BAD_REQUEST);
                    }
                    $requestSegmentIds[] = $requestSegment['id'];
                }
            }

            foreach ($entity->getLists() as $currentSegment) {
                if (!in_array($currentSegment->getId(), $requestSegmentIds)) {
                    $deletedSources['lists'][$currentSegment->getId()] = 'ignore';
                }
            }

            if (isset($parameters['forms'])) {
                foreach ($parameters['forms'] as $requestForm) {
                    if (!isset($requestForm['id'])) {
                        return $this->returnError('$campaign[forms]['.$key.']["id"] is missing', Response::HTTP_BAD_REQUEST);
                    }
                    $requestFormIds[] = $requestForm['id'];
                }
            }

            foreach ($entity->getForms() as $currentForm) {
                if (!in_array($currentForm->getId(), $requestFormIds)) {
                    $deletedSources['forms'][$currentForm->getId()] = 'ignore';
                }
            }
        }

        // Set lead sources
        $this->model->setLeadSources($entity, $currentSources, $deletedSources);

        // Build and set Event entities
        if (isset($parameters['events']) && isset($parameters['canvasSettings'])) {
            $this->model->setEvents($entity, $parameters['events'], $parameters['canvasSettings'], $deletedEvents);
        }

        /** @var array<ConstraintViolationListInterface<ConstraintViolationInterface>> $eventViolations */
        $eventViolations = array_filter(
            array_map(
                fn (Event $event) => $this->validator->validate($event),
                $entity->getEvents()->toArray()
            ),
            fn ($error) => $error->count() > 0
        );

        if (count($eventViolations) > 0) {
            $errors = [];
            foreach ($eventViolations as $violationList) {
                foreach ($violationList as $violation) {
                    \assert($violation instanceof ConstraintViolationInterface);
                    $errors[] = [
                        'code'    => $violation->getCode(),
                        'message' => $violation->getMessage(),
                        'details' => $violation->getPropertyPath(),
                        'type'    => 'validation',
                    ];
                }
            }

            $view = $this->view(['errors' => $errors], Response::HTTP_UNPROCESSABLE_ENTITY);

            return $this->handleView($view);
        }

        // Persist to the database before building connection so that IDs are available
        $this->model->saveEntity($entity);

        // Update canvas settings with new event IDs then save
        if (isset($parameters['canvasSettings'])) {
            $this->model->setCanvasSettings($entity, $parameters['canvasSettings']);
        }

        if (Request::METHOD_PUT === $method && !empty($deletedEvents)) {
            $this->eventModel->deleteEvents($entity->getEvents()->toArray(), $deletedEvents);
        }
    }

    /**
     * Change the array structure.
     *
     * @param array $events
     */
    public function modifyCampaignEventArray($events): array
    {
        $updatedEvents = [];

        if ($events && is_array($events)) {
            foreach ($events as $event) {
                if (!empty($event['id'])) {
                    $updatedEvents[$event['id']] = 'ignore';
                }
            }
        }

        return $updatedEvents;
    }

    /**
     * Obtains a list of campaign contacts.
     *
     * @return Response
     */
    public function getContactsAction(Request $request, $id)
    {
        $entity = $this->model->getEntity($id);

        if (null === $entity) {
            return $this->notFound();
        }

        if (!$this->checkEntityAccess($entity)) {
            return $this->accessDenied();
        }

        $where = InputHelper::clean($request->query->get('where') ?? []);
        $order = InputHelper::clean($request->query->get('order') ?? []);
        $start = (int) $request->query->get('start', 0);
        $limit = (int) $request->query->get('limit', 100);

        $where[] = [
            'col'  => 'campaign_id',
            'expr' => 'eq',
            'val'  => $id,
        ];

        $where[] = [
            'col'  => 'manually_removed',
            'expr' => 'eq',
            'val'  => 0,
        ];

        return $this->forward(
            'Mautic\CoreBundle\Controller\Api\StatsApiController::listAction',
            [
                'table'     => 'campaign_leads',
                'itemsName' => 'contacts',
                'order'     => $order,
                'where'     => $where,
                'start'     => $start,
                'limit'     => $limit,
            ]
        );
    }

    public function cloneCampaignAction($campaignId)
    {
        if (empty($campaignId) || false == intval($campaignId)) {
            return $this->notFound();
        }

        $original = $this->model->getEntity($campaignId);
        if (empty($original)) {
            return $this->notFound();
        }
        $entity = clone $original;

        if (!$this->checkEntityAccess($entity, 'create')) {
            return $this->accessDenied();
        }

        $this->model->saveEntity($entity);

        $headers = [];
        // return the newly created entities location if applicable

        $route               = 'mautic_api_campaigns_getone';
        $headers['Location'] = $this->generateUrl(
            $route,
            array_merge(['id' => $entity->getId()], $this->routeParams),
            true
        );

        $view = $this->view([$this->entityNameOne => $entity], Response::HTTP_OK, $headers);

        $this->setSerializationContext($view);

        return $this->handleView($view);
    }
}
