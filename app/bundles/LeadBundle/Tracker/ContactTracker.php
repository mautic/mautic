<?php

/*
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Tracker;

use Mautic\CoreBundle\Entity\IpAddress;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\IpLookupHelper;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadRepository;
use Mautic\LeadBundle\Event\LeadChangeEvent;
use Mautic\LeadBundle\LeadEvents;
use Mautic\LeadBundle\Tracker\Service\ContactTrackingService\ContactTrackingServiceInterface;
use Monolog\Logger;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class ContactTracker
{
    /**
     * @var LeadRepository
     */
    private $leadRepository;

    /**
     * @var ContactTrackingServiceInterface
     */
    private $contactTrackingService;

    /**
     * @var DeviceTracker
     */
    private $deviceTracker;

    /**
     * @var CorePermissions
     */
    private $security;

    /**
     * @var null|Lead
     */
    private $systemContact;

    /**
     * @var null|Lead
     */
    private $trackedContact;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var IpLookupHelper
     */
    private $ipLookupHelper;

    /**
     * @var Request
     */
    private $request;

    /**
     * @var bool
     */
    private $trackByIp;

    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;

    /**
     * ContactTracker constructor.
     *
     * @param LeadRepository                  $leadRepository
     * @param ContactTrackingServiceInterface $contactTrackingService
     * @param CorePermissions                 $security
     * @param Logger                          $logger
     * @param IpLookupHelper                  $ipLookupHelper
     * @param RequestStack                    $requestStack
     * @param CoreParametersHelper            $coreParametersHelper
     * @param EventDispatcherInterface        $dispatcher
     */
    public function __construct(
        LeadRepository $leadRepository,
        ContactTrackingServiceInterface $contactTrackingService,
        DeviceTracker $deviceTracker,
        CorePermissions $security,
        Logger $logger,
        IpLookupHelper $ipLookupHelper,
        RequestStack $requestStack,
        CoreParametersHelper $coreParametersHelper,
        EventDispatcherInterface $dispatcher
    ) {
        $this->leadRepository         = $leadRepository;
        $this->contactTrackingService = $contactTrackingService;
        $this->deviceTracker          = $deviceTracker;
        $this->security               = $security;
        $this->logger                 = $logger;
        $this->ipLookupHelper         = $ipLookupHelper;
        $this->request                = $requestStack->getCurrentRequest();
        $this->trackByIp              = $coreParametersHelper->getParameter('track_contact_by_ip');
        $this->dispatcher             = $dispatcher;
    }

    /**
     * @return Lead|null
     */
    public function getContact()
    {
        if ($systemContact = $this->getSystemContact()) {
            return $systemContact;
        }

        if (empty($this->trackedContact)) {
            $this->trackedContact = $this->getCurrentContact();
            $this->generateTrackingCookies();
        }

        if ($this->request) {
            $this->logger->addDebug('LEAD: Tracking session for contact ID# '.$this->trackedContact->getId().' through '.$this->request->getMethod().' '.$this->request->getRequestUri());
        }

        // Log last active for the tracked contact
        if (!defined('MAUTIC_LEAD_LASTACTIVE_LOGGED')) {
            $this->leadRepository->updateLastActive($this->trackedContact->getId());
            define('MAUTIC_LEAD_LASTACTIVE_LOGGED', 1);
        }

        return $this->trackedContact;
    }

    /**
     * Set the contact and generate cookies for future tracking.
     *
     * @param Lead $lead
     */
    public function setTrackedContact(Lead $trackedContact)
    {
        $this->logger->addDebug("LEAD: {$trackedContact->getId()} set as current lead.");

        if ($this->useSystemContact()) {
            // Overwrite system current lead
            $this->setSystemContact($trackedContact);

            return;
        }

        // Take note of previously tracked in order to dispatched change event
        $previouslyTrackedContact = (is_null($this->trackedContact)) ? null : $this->trackedContact;
        $previouslyTrackedId      = $this->getTrackingId();

        $this->trackedContact = $trackedContact;

        // Hydrate custom field data
        $fields = $trackedContact->getFields();
        if (empty($fields)) {
            $this->hydrateCustomFieldData($trackedContact);
        }

        // Set last active
        $this->trackedContact->setLastActive(new \DateTime());

        // If for whatever reason this contact has not been saved yet, don't generate tracking cookies
        if (!$trackedContact->getId()) {
            return;
        }

        if (!$previouslyTrackedContact) {
            // New lead, set the tracking cookie
            $this->generateTrackingCookies();

            return;
        }

        if ($previouslyTrackedContact->getId() != $this->trackedContact->getId()) {
            $this->dispatchContactChangeEvent($previouslyTrackedContact, $previouslyTrackedId);
        }
    }

    /**
     * System contact bypasses cookie tracking.
     *
     * @param Lead $lead
     */
    public function setSystemContact(Lead $lead)
    {
        $this->systemContact = $lead;
    }

    /**
     * @return null|string
     */
    public function getTrackingId()
    {
        // Use the new method first
        if ($trackedDevice = $this->deviceTracker->getTrackedDevice()) {
            return $trackedDevice->getTrackingId();
        }

        // That failed, so look for the old cookies
        return $this->contactTrackingService->getTrackedIdentifier();
    }

    /**
     * @return Lead|null
     */
    private function getSystemContact()
    {
        if ($this->isUserSession()) {
            $this->logger->addDebug('LEAD: In a Mautic user session');

            return null;
        }

        if ($this->useSystemContact()) {
            $this->logger->addDebug('LEAD: System lead is being used');
            if (null === $this->systemContact) {
                $this->systemContact = new Lead();
            }

            return $this->systemContact;
        }

        return null;
    }

    /**
     * @return Lead|null
     */
    private function getCurrentContact()
    {
        if ($lead = $this->getContactByTrackedDevice()) {
            return $lead;
        }

        return $this->getContactByIpAddress();
    }

    /**
     * @return Lead|null
     */
    private function getContactByTrackedDevice()
    {
        $lead = null;

        // Is there a device being tracked?
        if ($trackedDevice = $this->deviceTracker->getTrackedDevice()) {
            $lead = $trackedDevice->getLead();

            // Lead associations are not hydrated with custom field values by default
            $this->hydrateCustomFieldData($lead);
        }

        if (null === $lead) {
            // Check to see if a contact is being tracked via the old cookie method in order to migrate them to the new
            $lead = $this->contactTrackingService->getTrackedLead();
        }

        if ($lead) {
            $this->logger->addDebug("LEAD: Existing lead found with ID# {$lead->getId()}.");
        }

        return $lead;
    }

    /**
     * @return Lead
     */
    private function getContactByIpAddress()
    {
        $ip = $this->ipLookupHelper->getIpAddress();
        // if no trackingId cookie set the lead is not tracked yet so create a new one
        if (!$ip->isTrackable()) {
            // Don't save leads that are from a non-trackable IP by default
            return $this->createNewContact($ip, false);
        }

        if ($this->trackByIp) {
            /** @var Lead[] $leads */
            $leads = $this->leadRepository->getLeadsByIp($ip->getIpAddress());
            if (count($leads)) {
                $lead = $leads[0];
                $this->logger->addDebug("LEAD: Existing lead found with ID# {$lead->getId()}.");

                return $lead;
            }
        }

        return $this->createNewContact($ip);
    }

    /**
     * @param IpAddress $ip
     * @param bool      $persist
     *
     * @return Lead
     */
    private function createNewContact(IpAddress $ip, $persist = true)
    {
        //let's create a lead
        $lead = new Lead();
        $lead->addIpAddress($ip);
        $lead->setNewlyCreated(true);

        if ($persist) {
            // Purposively ignoring events for new visitors
            $this->leadRepository->saveEntity($lead);
            $this->hydrateCustomFieldData($lead);

            $this->logger->addDebug("LEAD: New lead created with ID# {$lead->getId()}.");
        }

        return $lead;
    }

    /**
     * @param Lead $lead
     */
    private function hydrateCustomFieldData(Lead $lead = null)
    {
        if (null === $lead) {
            return;
        }

        // Hydrate fields with custom field data
        $fields = $this->leadRepository->getFieldValues($lead->getId());
        $lead->setFields($fields);
    }

    /**
     * @return bool
     */
    private function useSystemContact()
    {
        return $this->isUserSession() || $this->systemContact || defined('IN_MAUTIC_CONSOLE') || $this->request === null;
    }

    /**
     * @return bool
     */
    private function isUserSession()
    {
        return !$this->security->isAnonymous();
    }

    /**
     * @param Lead $previouslyTrackedContact
     * @param      $previouslyTrackedId
     */
    private function dispatchContactChangeEvent(Lead $previouslyTrackedContact, $previouslyTrackedId)
    {
        $newTrackingId = $this->getTrackingId();
        $this->logger->addDebug(
            "LEAD: Tracking code changed from $previouslyTrackedId for contact ID# {$previouslyTrackedContact->getId()} to $newTrackingId for contact ID# {$this->trackedContact->getId()}"
        );

        if ($previouslyTrackedId !== null) {
            if ($this->dispatcher->hasListeners(LeadEvents::CURRENT_LEAD_CHANGED)) {
                $event = new LeadChangeEvent($previouslyTrackedContact, $previouslyTrackedId, $this->trackedContact, $newTrackingId);
                $this->dispatcher->dispatch(LeadEvents::CURRENT_LEAD_CHANGED, $event);
            }
        }
    }

    private function generateTrackingCookies()
    {
        if ($leadId = $this->trackedContact->getId() && $this->request !== null) {
            $this->deviceTracker->createDeviceFromUserAgent($this->trackedContact, $this->request->server->get('HTTP_USER_AGENT'));
        }
    }
}
