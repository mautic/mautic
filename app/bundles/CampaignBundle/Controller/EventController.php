<?php

namespace Mautic\CampaignBundle\Controller;

use Doctrine\Persistence\ManagerRegistry;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\EventCollector\EventCollector;
use Mautic\CampaignBundle\Form\Type\EventType;
use Mautic\CampaignBundle\Model\CampaignModel;
use Mautic\CoreBundle\Controller\FormController as CommonFormController;
use Mautic\CoreBundle\Factory\ModelFactory;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\DateTimeHelper;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Service\FlashBag;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\CoreBundle\Twig\Helper\DateHelper;
use Mautic\FormBundle\Helper\FormFieldHelper;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class EventController extends CommonFormController
{
    /**
     * @var string[]
     */
    private array $supportedEventTypes = [
        Event::TYPE_DECISION,
        Event::TYPE_ACTION,
        Event::TYPE_CONDITION,
    ];

    public function __construct(
        FormFactoryInterface $formFactory,
        FormFieldHelper $fieldHelper,
        private EventCollector $eventCollector,
        private DateHelper $dateHelper,
        ManagerRegistry $doctrine,
        ModelFactory $modelFactory,
        UserHelper $userHelper,
        CoreParametersHelper $coreParametersHelper,
        EventDispatcherInterface $dispatcher,
        Translator $translator,
        FlashBag $flashBag,
        RequestStack $requestStack,
        CorePermissions $security,
        private CampaignModel $campaignModel,
    ) {
        parent::__construct($formFactory, $fieldHelper, $doctrine, $modelFactory, $userHelper, $coreParametersHelper, $dispatcher, $translator, $flashBag, $requestStack, $security);
    }

    /**
     * @var array<string, array<string, mixed>>
     */
    private array $modifiedEvents = [];

    /**
     * @var array<int, string>
     */
    private array $deletedEvents = [];

    /**
     * Generates new form and processes post data.
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function newAction(Request $request)
    {
        $success = 0;
        $valid   = $cancelled   = false;
        $this->setCampaignElements($request->request);
        if ('1' === $request->request->get('submit')) {
            $event                = $request->request->all()['campaignevent'] ?? [];
            $type                 = $event['type'];
            $eventType            = $event['eventType'];
            $campaignId           = $event['campaignId'];
            $event['triggerDate'] = (!empty($event['triggerDate'])) ? (new DateTimeHelper($event['triggerDate']))->getDateTime() : null;
        } else {
            $type       = $request->query->get('type');
            $eventType  = $request->query->get('eventType');
            $campaignId = $request->query->get('campaignId');
            $anchorName = $request->query->get('anchor', '');
            $event      = [
                'type'            => $type,
                'eventType'       => $eventType,
                'campaignId'      => $campaignId,
                'anchor'          => $anchorName,
                'anchorEventType' => $request->query->get('anchorEventType', ''),
            ];
        }

        // set the eventType key for events
        if (!in_array($eventType, $this->supportedEventTypes)) {
            return $this->modalAccessDenied();
        }

        // ajax only for form fields
        if (!$type
            || !$request->isXmlHttpRequest()
            || !$this->security->isGranted(
                [
                    'campaign:campaigns:edit',
                    'campaign:campaigns:create',
                ],
                'MATCH_ONE'
            )
        ) {
            return $this->modalAccessDenied();
        }

        // fire the builder event
        $events = $this->eventCollector->getEventsArray();
        $form   = $this->formFactory->create(
            EventType::class,
            $event,
            [
                'action'   => $this->generateUrl('mautic_campaignevent_action', ['objectAction' => 'new']),
                'settings' => $events[$eventType][$type],
            ]
        );
        $event['settings'] = $events[$eventType][$type];

        $form->get('campaignId')->setData($campaignId);

        // Check for a submitted form and process it
        if ('1' === $request->request->get('submit')) {
            if (!$cancelled = $this->isFormCancelled($form)) {
                if ($valid = $this->isFormValid($form)) {
                    $success = 1;

                    // form is valid so process the data
                    $keyId = 'new'.bin2hex(random_bytes(32));

                    // save the properties to return with request
                    $modifiedEvents = $this->getModifiedEvents();
                    $formData       = $form->getData();
                    $event          = array_merge($event, $formData);
                    $event['id']    = $event['tempId']    = $keyId;
                    if (empty($event['name'])) {
                        // set it to the event default
                        $event['name'] = $this->translator->trans($event['settings']['label']);
                    }
                    $modifiedEvents[$keyId] = $event;
                    $this->modifiedEvents   = $modifiedEvents;
                } else {
                    $success = 0;
                }
            }
        }

        $viewParams = ['type' => $type];
        if ($cancelled || $valid) {
            $closeModal = true;
        } else {
            $closeModal = false;
            if (isset($event['settings']['formTheme'])) {
                $viewParams['formTheme'] = $event['settings']['formTheme'];
            }

            $viewParams['form']             = $form->createView();
            $viewParams['eventHeader']      = $this->translator->trans($event['settings']['label']);
            $viewParams['eventDescription'] = (!empty($event['settings']['description'])) ? $this->translator->trans(
                $event['settings']['description']
            ) : '';
        }

        $viewParams['hideTriggerMode'] = isset($event['settings']['hideTriggerMode']) && $event['settings']['hideTriggerMode'];

        $passthroughVars = [
            'mauticContent' => 'campaignEvent',
            'success'       => $success,
            'formSubmitted' => $form->isSubmitted(),
            'route'         => false,
        ];

        if (1 === $success && !empty($modifiedEvents)) {
            $passthroughVars['modifiedEvents'] = $modifiedEvents;
        }

        if (!empty($keyId)) {
            $passthroughVars = array_merge($passthroughVars, $this->eventViewVars($event, $campaignId, 'new'));
        }

        if ($closeModal) {
            // just close the modal
            $passthroughVars['closeModal'] = 1;

            return new JsonResponse($passthroughVars);
        } else {
            return $this->ajaxAction(
                $request,
                [
                    'contentTemplate' => '@MauticCampaign/Event/form.html.twig',
                    'viewParameters'  => $viewParams,
                    'passthroughVars' => $passthroughVars,
                ]
            );
        }
    }

    /**
     * Generates edit form and processes post data.
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function editAction(Request $request, $objectId)
    {
        $valid         = $cancelled = false;
        $method        = $request->getMethod();
        $campaignEvent = $request->request->all()['campaignevent'] ?? [];
        $campaignId    = 'POST' === $method && !empty($campaignEvent['campaignId'])
            ? $campaignEvent['campaignId']
            : $request->query->get('campaignId');

        $this->setCampaignElements($request->request);
        $event = $this->modifiedEvents[$objectId] ?? [];
        if (empty($event)) {
            $eventEntity = $this->getModel('campaign.event')->getEntity($objectId);
            if (null === $eventEntity) {
                return $this->modalAccessDenied();
            }
            $event = $eventEntity->convertToArray();
        }

        if ('1' === $request->request->get('submit')) {
            $event = array_merge($event, [
                'anchor'          => $campaignEvent['anchor'] ?? '',
                'anchorEventType' => $campaignEvent['anchorEventType'] ?? '',
            ]);
        } else {
            if (!isset($event['anchor']) && !empty($event['decisionPath'])) {
                // Used to generate label
                $event['anchor'] = $event['decisionPath'];
            }

            if ($request->query->has('anchor')) {
                // Override the anchor
                $event['anchor'] = $request->get('anchor');
            }

            if ($request->query->has('anchorEventType')) {
                // Override the anchorEventType
                $event['anchorEventType'] = $request->get('anchorEventType');
            }
        }

        /*
         * If we don't have an event, don't support the event type, this is not an
         * AJAX request, or we are not granted campaign edit/create, deny access.
         */
        if (empty($event)
            || empty($event['eventType'])
            || !in_array($event['eventType'], $this->supportedEventTypes)
            || !isset($event['type'])
            || !$request->isXmlHttpRequest()
            || !$this->security->isGranted(
                [
                    'campaign:campaigns:edit',
                    'campaign:campaigns:create',
                ],
                'MATCH_ONE'
            )
        ) {
            return $this->modalAccessDenied();
        }

        /**
         * Fire the CampaignBuilderEvent event to get all events.
         *
         * We can directly dereference the return value here to get
         * the supported events for this type because we already made
         * sure that we're accessing a supported event type above.
         *
         * Method getEventsArray() returns translated labels & descriptions
         */
        $supportedEvents = $this->eventCollector->getEventsArray()[$event['eventType']];
        $form            = $this->formFactory->create(
            EventType::class,
            (array) $event,
            [
                'action'   => $this->generateUrl('mautic_campaignevent_action', ['objectAction' => 'edit', 'objectId' => $objectId]),
                'settings' => $supportedEvents[$event['type']],
            ]
        );
        $event['settings'] = $supportedEvents[$event['type']];

        $form->get('campaignId')->setData($campaignId);
        $modifiedEvents = $this->getModifiedEvents();

        // Check for a submitted form and process it
        if ('1' === $request->request->get('submit')) {
            if (!$cancelled = $this->isFormCancelled($form)) {
                if ($valid = $this->isFormValid($form)) {
                    $formData = $form->getData();
                    $event    = array_merge($event, $formData);

                    // Set the name to the event default if not known.
                    if (empty($event['name'])) {
                        $event['name'] = $event['settings']['label'];
                    }
                    $modifiedEvents[$objectId] = $event;
                }
            }
        }

        $viewParams = [
            'type'            => $event['type'],
            'hideTriggerMode' => isset($event['settings']['hideTriggerMode']) && $event['settings']['hideTriggerMode'],
        ];

        $passthroughVars = [
            'mauticContent' => 'campaignEvent',
            'success'       => !$cancelled && $valid,
            'formSubmitted' => $form->isSubmitted(),
            'route'         => false,
            'modifiedEvents'=> $modifiedEvents,
            'eventId'       => $event['id'] ?? '',
            'event'         => $event,
        ];

        if (!$cancelled && !$valid) {
            if (isset($event['settings']['formTheme'])) {
                $viewParams['formTheme'] = $event['settings']['formTheme'];
            }

            $viewParams = array_merge($viewParams, [
                'form'             => $form->createView(),
                'eventHeader'      => $event['settings']['label'],
                'eventDescription' => $event['settings']['description'],
            ]);

            return $this->ajaxAction(
                $request,
                [
                    'contentTemplate' => '@MauticCampaign/Event/form.html.twig',
                    'viewParameters'  => $viewParams,
                    'passthroughVars' => $passthroughVars,
                ]
            );
        }

        if (!$cancelled && $valid) {
            $passthroughVars = array_merge($passthroughVars, $this->eventViewVars($event, $campaignId, 'edit'));
        }

        // Just close the modal
        $passthroughVars['closeModal'] = 1;

        return new JsonResponse($passthroughVars);
    }

    /**
     * Deletes the entity.
     *
     * @return JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function deleteAction(Request $request, $objectId)
    {
        $this->setCampaignElements($request->request);
        $modifiedEvents = $this->getModifiedEvents();
        $deletedEvents  = $this->deletedEvents;

        // ajax only for form fields
        if (!$request->isXmlHttpRequest()
            || !$this->security->isGranted(
                [
                    'campaign:campaigns:edit',
                    'campaign:campaigns:create',
                ],
                'MATCH_ONE'
            )
        ) {
            return $this->accessDenied();
        }

        $event = (array_key_exists($objectId, $modifiedEvents)) ? $modifiedEvents[$objectId] : null;

        if ('POST' == $request->getMethod() && null !== $event) {
            $events            = $this->eventCollector->getEventsArray();
            $event['settings'] = $events[$event['eventType']][$event['type']];

            // Add the field to the delete list
            if (!in_array($objectId, $deletedEvents)) {
                // If event is new don't add to deleted list
                if (!str_contains($objectId, 'new')) {
                    $deletedEvents[] = $objectId;
                }

                // Always remove from modified list if deleted
                if (isset($modifiedEvents[$objectId])) {
                    unset($modifiedEvents[$objectId]);
                }
            }

            $dataArray = [
                'mauticContent' => 'campaignEvent',
                'success'       => 1,
                'route'         => false,
                'eventId'       => $objectId,
                'deleted'       => 1,
                'event'         => $event,
                'deletedEvents' => $deletedEvents,
            ];
        } else {
            $dataArray = ['success' => 0];
        }

        return new JsonResponse($dataArray);
    }

    /**
     * Undeletes the entity.
     *
     * @return JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function undeleteAction(Request $request, $objectId)
    {
        $campaignId     = $request->query->get('campaignId');
        $this->setCampaignElements($request->request);
        $modifiedEvents = $this->getModifiedEvents();
        $deletedEvents  = $this->deletedEvents;

        // ajax only for form fields
        if (!$request->isXmlHttpRequest()
            || !$this->security->isGranted(
                [
                    'campaign:campaigns:edit',
                    'campaign:campaigns:create',
                ],
                'MATCH_ONE'
            )
        ) {
            return $this->accessDenied();
        }

        $event = (array_key_exists($objectId, $modifiedEvents)) ? $modifiedEvents[$objectId] : null;

        if ('POST' == $request->getMethod() && null !== $event) {
            $events            = $this->eventCollector->getEventsArray();
            $event['settings'] = $events[$event['eventType']][$event['type']];

            // add the field to the delete list
            if (in_array($objectId, $deletedEvents)) {
                $key = array_search($objectId, $deletedEvents);
                unset($deletedEvents[$key]);
            }

            $template = (empty($event['settings']['template'])) ? '@MauticCampaign/Event/_generic.html.twig'
                : $event['settings']['template'];

            // prevent undefined errors
            $entity = new Event();
            $blank  = $entity->convertToArray();
            $event  = array_merge($blank, $event);

            $dataArray = [
                'mauticContent' => 'campaignEvent',
                'success'       => 1,
                'route'         => false,
                'eventId'       => $objectId,
                'eventHtml'     => $this->renderView(
                    $template,
                    [
                        'event'      => $event,
                        'id'         => $objectId,
                        'campaignId' => $campaignId,
                    ]
                ),
                'deletedEvents' => $deletedEvents,
            ];
        } else {
            $dataArray = ['success' => 0];
        }

        return new JsonResponse($dataArray);
    }

    public function cloneAction(Request $request, string $objectId): JsonResponse
    {
        $campaignId     = $request->query->get('campaignId');
        $session        = $request->getSession();
        $this->setCampaignElements($request->request);
        $modifiedEvents = $this->getModifiedEvents();
        $campaign       = $this->campaignModel->getEntity($campaignId);

        // ajax only for form fields
        if (!$request->isXmlHttpRequest()
            || !$this->security->isGranted(
                [
                    'campaign:campaigns:edit',
                    'campaign:campaigns:create',
                ],
                'MATCH_ONE'
            )
        ) {
            return $this->accessDenied();
        }

        $event = (array_key_exists($objectId, $modifiedEvents)) ? $modifiedEvents[$objectId] : null;

        if ('POST' == $request->getMethod() && null !== $event) {
            $keyId          = 'new'.hash('sha1', uniqid((string) mt_rand()));
            $event['id']    = $event['tempId']    = $keyId;
            $session->set('mautic.campaign.events.clone.storage', $event);

            $dataArray = [
                'success'       => 1,
                'mauticContent' => 'campaignEventClone',
                'route'         => false,
                'eventId'       => $objectId,
                'eventName'     => $event['name'],
                'eventType'     => $event['eventType'],
                'type'          => $event['type'],
                'campaignId'    => $campaign ? $campaign->getId() : $campaignId,
                'campaignName'  => $campaign ? $campaign->getName() : $this->translator->trans('mautic.campaign.event.clone.new.campaign'),
            ];
        } else {
            $dataArray = ['success' => 0];
        }

        return new JsonResponse($dataArray);
    }

    public function insertAction(Request $request): JsonResponse
    {
        $campaignId     = $request->query->get('campaignId');
        $session        = $request->getSession();
        $this->setCampaignElements($request->request);
        $event          = $session->get('mautic.campaign.events.clone.storage');

        if (empty($event)) {
            return new JsonResponse([
                'error' => $this->translator->trans('mautic.campaign.event.clone.request.missing'),
            ], 400);
        }
        $session->remove('mautic.campaign.events.clone.storage');

        $keyId          = 'new'.hash('sha1', uniqid((string) mt_rand()));
        $event['id']    = $event['tempId'] = $keyId;

        $modifiedEvents[$keyId] = $event;
        $this->modifiedEvents   = $modifiedEvents;

        $passThroughVars               = [
            'mauticContent'     => 'campaignEvent',
            'clearCloneStorage' => true,
            'success'           => 1,
            'route'             => false,
        ];

        $passThroughVars = array_merge($passThroughVars, $this->eventViewVars($event, $campaignId, 'insert'));

        return new JsonResponse($passThroughVars);
    }

    /**
     * @param array<string, mixed> $event
     *
     * @return array<string, mixed>
     */
    private function eventViewVars(
        array $event,
        string $campaignId,
        string $action,
    ): array {
        // Merge default event properties with provided event data
        $event = array_merge((new Event())->convertToArray(), $event);

        // Determine the template
        $template = $event['settings']['template'] ?? '@MauticCampaign/Event/_generic.html.twig';

        // Prepare common template variables
        $templateVars = [
            'event'      => $event,
            'id'         => $event['id'],
            'campaignId' => $campaignId,
        ];
        if ('edit' === $action) {
            $templateVars['update']        = true;
        }

        // Render the template and store it in the appropriate variable
        $passThroughKey                   = ('edit' === $action) ? 'updateHtml' : 'eventHtml';
        $passThroughVars[$passThroughKey] = $this->renderView($template, $templateVars);

        // Pass through event-related variables
        $passThroughVars += [
            'event'     => $event,
            'eventId'   => $event['id'],
            'eventType' => $event['eventType'],
        ];

        // Handle trigger mode interval
        if (Event::TRIGGER_MODE_INTERVAL === $event['triggerMode']) {
            $label = 'mautic.campaign.connection.trigger.interval.label';

            if (Event::PATH_INACTION === $event['anchor']) {
                $label .= '_inaction';
            }

            $passThroughVars['label'] = $this->translator->trans(
                $label,
                [
                    '%number%' => $event['triggerInterval'],
                    '%unit%'   => $this->translator->trans(
                        'mautic.campaign.event.intervalunit.'.$event['triggerIntervalUnit'],
                        ['%count%' => $event['triggerInterval']]
                    ),
                ]
            );
        }

        // Handle trigger mode date
        if (Event::TRIGGER_MODE_DATE === $event['triggerMode']) {
            $label = 'mautic.campaign.connection.trigger.date.label';

            if (Event::PATH_INACTION === $event['anchor']) {
                $label .= '_inaction';
            }

            $passThroughVars['label'] = $this->translator->trans(
                $label,
                [
                    '%full%' => $this->dateHelper->toFull($event['triggerDate']),
                    '%time%' => $this->dateHelper->toTime($event['triggerDate']),
                    '%date%' => $this->dateHelper->toShort($event['triggerDate']),
                ]
            );
        }

        return $passThroughVars;
    }

    private function setCampaignElements(ParameterBag $request): void
    {
        if ($request->get('modifiedEvents')) {
            $this->modifiedEvents = json_decode($request->get('modifiedEvents'), true);
        }
        if ($request->get('deletedEvents')) {
            $this->deletedEvents = json_decode($request->get('deletedEvents'), true);
        }
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function getModifiedEvents(): array
    {
        return $this->modifiedEvents;
    }
}
