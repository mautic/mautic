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
use Mautic\LeadBundle\Event\LeadEvent;
use Mautic\LeadBundle\LeadEvents;
use Mautic\LeadBundle\Model\DefaultValueTrait;
use Mautic\LeadBundle\Model\FieldModel;
use Mautic\LeadBundle\Tracker\Service\ContactTrackingService\ContactTrackingServiceInterface;
use Monolog\Logger;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class ContactTracker
{
    use DefaultValueTrait;
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
     * @var Lead|null
     */
    private $systemContact;

    /**
     * @var Lead|null
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
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var CoreParametersHelper
     */
    private $coreParametersHelper;

    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;

    /**
     * @var FieldModel
     */
    private $leadFieldModel;

    /**
     * ContactTracker constructor.
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
        EventDispatcherInterface $dispatcher,
        FieldModel $leadFieldModel
    ) {
        $this->leadRepository         = $leadRepository;
        $this->contactTrackingService = $contactTrackingService;
        $this->deviceTracker          = $deviceTracker;
        $this->security               = $security;
        $this->logger                 = $logger;
        $this->ipLookupHelper         = $ipLookupHelper;
        $this->requestStack           = $requestStack;
        $this->coreParametersHelper   = $coreParametersHelper;
        $this->dispatcher             = $dispatcher;
        $this->leadFieldModel         = $leadFieldModel;
    }

    /**
     * @return Lead|null
     */
    public function getContact()
    {
        $request = $this->requestStack->getCurrentRequest();

        if ($systemContact = $this->getSystemContact()) {
            return $systemContact;
        } elseif ($this->isUserSession()) {
            return null;
        }

        if (empty($this->trackedContact)) {
            $this->trackedContact = $this->getCurrentContact();
            $this->generateTrackingCookies();
        }

        if ($request) {
            $this->logger->addDebug('CONTACT: Tracking session for contact ID# '.$this->trackedContact->getId().' through '.$request->getMethod().' '.$request->getRequestUri());
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
     */
    public function setTrackedContact(Lead $trackedContact)
    {
        $this->logger->addDebug("CONTACT: {$trackedContact->getId()} set as current lead.");

        if ($this->useSystemContact()) {
            // Overwrite system current lead
            $this->setSystemContact($trackedContact);

            return;
        }

        // Take note of previously tracked in order to dispatched change event
        $previouslyTrackedContact = (is_null($this->trackedContact)) ? null : $this->trackedContact;
        $previouslyTrackedId      = $this->getTrackingId();

        // Set the newly tracked contact
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
            // Delete existing cookies to prevent tracking as someone else
            $this->deviceTracker->clearTrackingCookies();

            return;
        }

        // Generate cookies for the newly tracked contact
        $this->generateTrackingCookies();

        if ($previouslyTrackedContact && $previouslyTrackedContact->getId() != $this->trackedContact->getId()) {
            $this->dispatchContactChangeEvent($previouslyTrackedContact, $previouslyTrackedId);
        }
    }

    /**
     * System contact bypasses cookie tracking.
     */
    public function setSystemContact(Lead $lead = null)
    {
        if (null !== $lead) {
            $this->logger->addDebug("LEAD: {$lead->getId()} set as system lead.");

            $fields = $lead->getFields();
            if (empty($fields)) {
                $this->hydrateCustomFieldData($lead);
            }
        }

        $this->systemContact = $lead;
    }

    /**
     * @return string|null
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
        if ($this->useSystemContact() && $this->systemContact) {
            $this->logger->addDebug('CONTACT: System lead is being used');

            return $this->systemContact;
        }

        if ($this->isUserSession()) {
            $this->logger->addDebug('CONTACT: In a Mautic user session');
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

        // Return null for leads that are from a non-trackable IP, prevent anonymous lead with a non-trackable IP to be tracked
        $ip = $this->ipLookupHelper->getIpAddress();
        if ($ip && !$ip->isTrackable()) {
            return $lead;
        }

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
            $this->logger->addDebug("CONTACT: Existing lead found with ID# {$lead->getId()}.");
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
        if ($ip && !$ip->isTrackable()) {
            // Don't save leads that are from a non-trackable IP by default
            return $this->createNewContact($ip, false);
        }

        if ($this->coreParametersHelper->get('track_contact_by_ip') && $this->coreParametersHelper->get('anonymize_ip')) {
            /** @var Lead[] $leads */
            $leads = $this->leadRepository->getLeadsByIp($ip->getIpAddress());
            if (count($leads)) {
                $lead = $leads[0];
                $this->logger->addDebug("CONTACT: Existing lead found with ID# {$lead->getId()}.");

                return $lead;
            }
        }

        return $this->createNewContact($ip);
    }

    /**
     * @param bool $persist
     *
     * @return Lead
     */
    private function createNewContact(IpAddress $ip = null, $persist = true)
    {
        //let's create a lead
        $lead = new Lead();
        $lead->setNewlyCreated(true);

        if ($ip) {
            $lead->addIpAddress($ip);
        }

        if ($persist && !defined('MAUTIC_NON_TRACKABLE_REQUEST')) {
            // Dispatch events for new lead to write create log, ip address change, etc
            $event = new LeadEvent($lead, true);
            $this->dispatcher->dispatch(LeadEvents::LEAD_PRE_SAVE, $event);
            $this->setEntityDefaultValues($lead);
            $this->leadRepository->saveEntity($lead);
            $this->hydrateCustomFieldData($lead);

            $this->dispatcher->dispatch(LeadEvents::LEAD_POST_SAVE, $event);

            $this->logger->addDebug("CONTACT: New lead created with ID# {$lead->getId()}.");
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
        return $this->isUserSession() || $this->systemContact || defined('IN_MAUTIC_CONSOLE') || null === $this->requestStack->getCurrentRequest();
    }

    /**
     * @return bool
     */
    private function isUserSession()
    {
        return !$this->security->isAnonymous();
    }

    /**
     * @param $previouslyTrackedId
     */
    private function dispatchContactChangeEvent(Lead $previouslyTrackedContact, $previouslyTrackedId)
    {
        $newTrackingId = $this->getTrackingId();
        $this->logger->addDebug(
            "CONTACT: Tracking code changed from $previouslyTrackedId for contact ID# {$previouslyTrackedContact->getId()} to $newTrackingId for contact ID# {$this->trackedContact->getId()}"
        );

        if (null !== $previouslyTrackedId) {
            if ($this->dispatcher->hasListeners(LeadEvents::CURRENT_LEAD_CHANGED)) {
                $event = new LeadChangeEvent($previouslyTrackedContact, $previouslyTrackedId, $this->trackedContact, $newTrackingId);
                $this->dispatcher->dispatch(LeadEvents::CURRENT_LEAD_CHANGED, $event);
            }
        }
    }

    private function generateTrackingCookies()
    {
        $request = $this->requestStack->getCurrentRequest();
        if ($leadId = $this->trackedContact->getId() && null !== $request) {
            $this->deviceTracker->createDeviceFromUserAgent($this->trackedContact, $request->server->get('HTTP_USER_AGENT'));
        }
    }
}
