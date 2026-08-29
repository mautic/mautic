<?php

namespace Mautic\CampaignBundle\Controller\Api;

use Doctrine\Persistence\ManagerRegistry;
use FOS\RestBundle\View\View;
use Mautic\ApiBundle\Controller\FetchCommonApiController;
use Mautic\ApiBundle\Helper\EntityResultHelper;
use Mautic\ApiBundle\Serializer\Exclusion\FieldInclusionStrategy;
use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\Entity\LeadEventLog;
use Mautic\CampaignBundle\Model\CampaignModel;
use Mautic\CampaignBundle\Model\EventLogModel;
use Mautic\CampaignBundle\Model\EventModel;
use Mautic\CoreBundle\Factory\ModelFactory;
use Mautic\CoreBundle\Helper\AppVersion;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\LeadBundle\Controller\LeadAccessTrait;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Model\LeadModel;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;

/**
 * @extends FetchCommonApiController<LeadEventLog>
 */
final class EventLogApiController extends FetchCommonApiController
{
    use LeadAccessTrait;

    private const int LOG_SERIALIZATION = 30;

    private ?Campaign $campaign = null;

    private ?Lead $contact = null;

    /**
     * @var EventLogModel|null
     */
    protected $model;

    public function __construct(
        CorePermissions $security,
        Translator $translator,
        EntityResultHelper $entityResultHelper,
        AppVersion $appVersion,
        RequestStack $requestStack,
        ManagerRegistry $doctrine,
        ModelFactory $modelFactory,
        EventDispatcherInterface $dispatcher,
        CoreParametersHelper $coreParametersHelper,
        EventLogModel $campaignEventLogModel,
        private readonly LeadModel $leadModel,
        private readonly CampaignModel $campaignModel,
        private readonly EventModel $eventModel,
    ) {
        $this->model                    = $campaignEventLogModel;
        $this->entityClass              = LeadEventLog::class;
        $this->entityNameOne            = 'event';
        $this->entityNameMulti          = 'events';
        $this->parentChildrenLevelDepth = 1;
        $this->serializerGroups         = [
            'campaignList',
            'ipAddressList',
            self::LOG_SERIALIZATION => 'campaignEventLogDetails',
        ];

        // Only include the id of the parent
        $this->addExclusionStrategy(new FieldInclusionStrategy(['id'], 1, 'parent'));

        parent::__construct($security, $translator, $entityResultHelper, $appVersion, $requestStack, $doctrine, $modelFactory, $dispatcher, $coreParametersHelper);
    }

    public function getEntitiesAction(Request $request, UserHelper $userHelper): Response
    {
        $this->serializerGroups[self::LOG_SERIALIZATION] = 'campaignEventStandaloneLogDetails';
        $this->serializerGroups[]                        = 'campaignEventStandaloneList';
        $this->serializerGroups[]                        = 'leadBasicList';

        return parent::getEntitiesAction($request, $userHelper);
    }

    /**
     * Get a list of events.
     */
    public function getContactEventsAction(Request $request, UserHelper $userHelper, $contactId, $campaignId = null): Response
    {
        // Ensure contact exists and user has access
        $contact = $this->checkLeadAccess($contactId, 'view');
        if ($contact instanceof Response) {
            return $contact;
        }

        // Ensure campaign exists and user has access
        if (!empty($campaignId)) {
            $campaign = $this->campaignModel->getEntity($campaignId);
            if (null == $campaign || !$campaign->getId()) {
                return $this->notFound();
            }
            if (!$this->checkEntityAccess($campaign)) {
                return $this->accessDenied();
            }
            // Check that contact is part of the campaign
            $membership = $campaign->getContactMembership($contact);
            if (0 === count($membership)) {
                return $this->returnError(
                    $this->translator->trans(
                        'mautic.campaign.error.contact_not_in_campaign',
                        ['%campaign%' => $campaignId, '%contact%' => $contactId]
                    ),
                    Response::HTTP_CONFLICT
                );
            }

            $this->campaign           = $campaign;
            $this->serializerGroups[] = 'campaignEventWithLogsList';
            $this->serializerGroups[] = 'campaignLeadList';
        } else {
            unset($this->serializerGroups[self::LOG_SERIALIZATION]);
            $this->serializerGroups[] = 'campaignEventStandaloneList';
            $this->serializerGroups[] = 'campaignEventStandaloneLogDetails';
        }

        $this->contact                   = $contact;
        $this->extraGetEntitiesArguments = [
            'contact_id'  => $contactId,
            'campaign_id' => $campaignId,
        ];

        return $this->getEntitiesAction($request, $userHelper);
    }

    public function editContactEventAction(Request $request, $eventId, $contactId): Response
    {
        $parameters = $request->request->all();

        // Ensure contact exists and user has access
        $contact = $this->checkLeadAccess($contactId, 'edit');
        if ($contact instanceof Response) {
            return $contact;
        }
        /** @var Event $event */
        $event = $this->eventModel->getEntity($eventId);
        if (null === $event || !$event->getId()) {
            return $this->notFound();
        }

        // Ensure campaign edit access
        $campaign = $event->getCampaign();
        if (!$this->checkEntityAccess($campaign, 'edit')) {
            return $this->accessDenied();
        }

        $result = $this->model->updateContactEvent($event, $contact, $parameters);

        if (is_string($result)) {
            return $this->returnError($result, Response::HTTP_CONFLICT);
        }
        [$log, $created] = $result;

        $event->addContactLog($log);
        $view = $this->view(
            [
                $this->entityNameOne => $event,
            ],
            ($created) ? Response::HTTP_CREATED : Response::HTTP_OK
        );
        $this->serializerGroups[] = 'campaignEventWithLogsDetails';
        $this->serializerGroups[] = 'campaignBasicList';
        $this->setSerializationContext($view);

        return $this->handleView($view);
    }

    public function editEventsAction(Request $request): Response
    {
        $parameters = $request->request->all();

        $valid = $this->validateBatchPayload($parameters);
        if ($valid instanceof Response) {
            return $valid;
        }

        $errors= [];

        $events   = $this->getBatchEntities($parameters, $errors, false, 'eventId', $this->eventModel, false);
        $contacts = $this->getBatchEntities($parameters, $errors, false, 'contactId', $this->leadModel, false);

        $this->inBatchMode = true;
        $errors            = [];
        foreach ($parameters as $key => $params) {
            if (!isset($params['eventId']) || !isset($params['contactId']) || !isset($events[$params['eventId']])
                || !isset($contacts[$params['contactId']])
            ) {
                $errors[$key] = $this->notFound('mautic.campaign.error.edit_events.request_invalid');

                continue;
            }

            $event = $events[$params['eventId']];

            // Ensure contact exists and user has access
            $contact = $this->checkLeadAccess($contacts[$params['contactId']], 'edit');
            if ($contact instanceof Response) {
                $errors[$key] = $contact->getContent();

                continue;
            }

            // Ensure campaign edit access
            $campaign = $event->getCampaign();
            if (!$this->checkEntityAccess($campaign, 'edit')) {
                $errors[$key] = $this->accessDenied();

                continue;
            }

            $result = $this->model->updateContactEvent($event, $contact, $params);

            if (is_string($result)) {
                $errors[$key] = $this->returnError($result, Response::HTTP_CONFLICT);
            } else {
                [$log, $created] = $result;
                $event->addContactLog($log);
            }
        }

        $payload = [
            $this->entityNameMulti => $events,
        ];

        if ([] !== $errors) {
            $payload['errors'] = $errors;
        }

        $view                     = $this->view($payload, Response::HTTP_OK);
        $this->serializerGroups[] = 'campaignEventWithLogsList';
        $this->setSerializationContext($view);

        return $this->handleView($view);
    }

    protected function view($data = null, ?int $statusCode = null, array $headers = []): View
    {
        if ($this->campaign instanceof \Mautic\CampaignBundle\Entity\Campaign) {
            $data['campaign'] = $this->campaign;

            if ($this->contact instanceof \Mautic\LeadBundle\Entity\Lead) {
                [$data['membership'], $ignore] = $this->prepareEntitiesForView($this->campaign->getContactMembership($this->contact));
            }
        }

        return parent::view($data, $statusCode, $headers);
    }
}
