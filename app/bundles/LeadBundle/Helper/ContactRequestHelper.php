<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Helper;

use Mautic\CoreBundle\Helper\ClickthroughHelper;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\IpLookupHelper;
use Mautic\LeadBundle\DataObject\LeadManipulator;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadDeviceRepository;
use Mautic\LeadBundle\Event\ContactIdentificationEvent;
use Mautic\LeadBundle\Exception\ContactNotFoundException;
use Mautic\LeadBundle\LeadEvents;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\LeadBundle\Tracker\ContactTracker;
use Monolog\Logger;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class ContactRequestHelper
{
    /**
     * @var LeadModel
     */
    private $leadModel;

    /**
     * @var CoreParametersHelper
     */
    private $coreParametersHelper;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @var LeadDeviceRepository
     */
    private $leadDeviceRepository;

    /**
     * @var IpLookupHelper
     */
    private $ipLookupHelper;

    /**
     * @var ContactTracker
     */
    private $contactTracker;

    /**
     * @var null|\Symfony\Component\HttpFoundation\Request
     */
    private $request;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var Lead
     */
    private $trackedContact;

    /**
     * @var array
     */
    private $queryFields = [];

    /**
     * @var array
     */
    private $publiclyUpdatableFieldValues = [];

    /**
     * ContactRequestHelper constructor.
     *
     * @param LeadModel                $leadModel
     * @param ContactTracker           $contactTracker
     * @param CoreParametersHelper     $coreParametersHelper
     * @param IpLookupHelper           $ipLookupHelper
     * @param LeadDeviceRepository     $leadDeviceRepository
     * @param RequestStack             $requestStack
     * @param Logger                   $logger
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(
        LeadModel $leadModel,
        ContactTracker $contactTracker,
        CoreParametersHelper $coreParametersHelper,
        IpLookupHelper $ipLookupHelper,
        LeadDeviceRepository $leadDeviceRepository,
        RequestStack $requestStack,
        Logger $logger,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->leadModel            = $leadModel;
        $this->contactTracker       = $contactTracker;
        $this->coreParametersHelper = $coreParametersHelper;
        $this->ipLookupHelper       = $ipLookupHelper;
        $this->leadDeviceRepository = $leadDeviceRepository;
        $this->request              = $requestStack->getCurrentRequest();
        $this->logger               = $logger;
        $this->eventDispatcher      = $eventDispatcher;
    }

    /**
     * @param array $queryFields
     *
     * @return Lead
     */
    public function getContactFromQuery(array $queryFields = [])
    {
        $this->trackedContact = $this->contactTracker->getContact();
        $this->queryFields    = $queryFields;
        try {
            $foundContact         = $this->getContactFromUrl();
            $this->trackedContact = $foundContact;
            $this->contactTracker->setTrackedContact($this->trackedContact);
        } catch (ContactNotFoundException $exception) {
        }

        $this->prepareContactFromRequest();

        return $this->trackedContact;
    }

    /**
     * @return Lead
     *
     * @throws ContactNotFoundException
     */
    private function getContactFromUrl()
    {
        // Check for a lead requested through clickthrough query parameter
        if (isset($this->queryFields['ct'])) {
            $clickthrough = (is_array($this->queryFields['ct'])) ? $this->queryFields['ct'] : ClickthroughHelper::decodeArrayFromUrl($this->queryFields['ct']);
        } elseif ($clickthrough = $this->request->get('ct', [])) {
            $clickthrough = ClickthroughHelper::decodeArrayFromUrl($clickthrough);
        }

        if (!is_array($clickthrough)) {
            throw new ContactNotFoundException();
        }

        try {
            $contact = $this->getContactFromClickthrough($clickthrough);

            return $contact;
        } catch (ContactNotFoundException $exception) {
        }

        $this->setEmailFromClickthroughIdentification($clickthrough);

        /* @var Lead $foundContact */
        if (!empty($this->queryFields)) {
            list($foundContact, $this->publiclyUpdatableFieldValues) = $this->leadModel->checkForDuplicateContact(
                $this->queryFields,
                $this->trackedContact,
                true,
                true
            );
            if ($foundContact->getId() !== $this->trackedContact->getId()) {
                // A contact was found by a publicly updatable field
                return $foundContact;
            }
        }

        return $this->getContactByFingerprint();
    }

    /**
     * Identify a contact through a clickthrough URL.
     *
     * @param array $clickthrough
     *
     * @return Lead
     *
     * @throws ContactNotFoundException
     */
    private function getContactFromClickthrough(array $clickthrough)
    {
        $event = new ContactIdentificationEvent($clickthrough);
        $this->eventDispatcher->dispatch(LeadEvents::ON_CLICKTHROUGH_IDENTIFICATION, $event);

        if ($contact = $event->getIdentifiedContact()) {
            $this->logger->addDebug("LEAD: Contact ID# {$contact->getId()} tracked through clickthrough query by the ".$event->getIdentifier().' channel');

            // Merge tracked visitor into the clickthrough contact
            return $this->mergeWithTrackedContact($contact);
        }

        throw new ContactNotFoundException();
    }

    /**
     * @param array $clickthrough
     */
    private function setEmailFromClickthroughIdentification(array $clickthrough)
    {
        if (!$this->coreParametersHelper->getParameter('track_by_tracking_url') || !empty($queryFields['email'])) {
            return;
        }

        if (empty($clickthrough['lead']) || !$foundContact = $this->leadModel->getEntity($clickthrough['lead'])) {
            return;
        }

        // Identify contact from link if email field is set as publicly updateable
        if ($email = $foundContact->getEmail()) {
            // Add email to query for checkForDuplicateContact to pick up and merge
            $this->queryFields['email'] = $email;
            $this->logger->addDebug("LEAD: Contact ID# {$clickthrough['lead']} tracked through clickthrough query.");

            return;
        }
    }

    /**
     * @return Lead
     *
     * @throws ContactNotFoundException
     */
    private function getContactByFingerprint()
    {
        if (!$this->coreParametersHelper->getParameter('track_by_fingerprint')) {
            // Track by fingerprint is disabled so just use tracked lead
            throw new ContactNotFoundException();
        }

        if (!$this->trackedContact->isAnonymous() || empty($this->queryFields['fingerprint'])) {
            // We already know who this is or fingerprint is not available so just use tracked lead
            throw new ContactNotFoundException();
        }

        if ($device = $this->leadDeviceRepository->getDeviceByFingerprint($this->queryFields['fingerprint'])) {
            $deviceLead = $this->leadModel->getEntity($device['lead_id']);

            $this->logger->addDebug("LEAD: Contact ID# {$deviceLead->getId()} tracked through fingerprint.");

            // Merge tracked visitor into the contact found by fingerprint
            return $this->mergeWithTrackedContact($deviceLead);
        }

        throw new ContactNotFoundException();
    }

    private function prepareContactFromRequest()
    {
        $ipAddress          = $this->ipLookupHelper->getIpAddress();
        $contactIpAddresses = $this->trackedContact->getIpAddresses();
        if (!$contactIpAddresses->contains($ipAddress)) {
            $this->trackedContact->addIpAddress($ipAddress);
        }

        if (!empty($this->publiclyUpdatableFieldValues)) {
            $this->leadModel->setFieldValues(
                $this->trackedContact,
                $this->publiclyUpdatableFieldValues,
                false,
                true,
                true
            );
        }

        // Assume a web request as this is likely a tracking request from DWC or tracking code
        $this->trackedContact->setManipulator(
            new LeadManipulator(
                'page',
                'hit',
                null,
                (isset($this->queryFields['page_url'])) ? $this->queryFields['page_url'] : ''
            )
        );

        if (isset($this->queryFields['tags'])) {
            $this->leadModel->modifyTags($this->trackedContact, $this->queryFields['tags']);
        }
    }

    /**
     * @param Lead $foundContact
     *
     * @return Lead
     */
    private function mergeWithTrackedContact(Lead $foundContact)
    {
        if ($this->trackedContact && $this->trackedContact->getId() && $this->trackedContact->isAnonymous()) {
            return $this->leadModel->mergeLeads($this->trackedContact, $foundContact, false);
        }

        return $foundContact;
    }
}
