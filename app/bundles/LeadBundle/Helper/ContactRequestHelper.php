<?php

namespace Mautic\LeadBundle\Helper;

use Mautic\CoreBundle\Helper\ClickthroughHelper;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\IpLookupHelper;
use Mautic\LeadBundle\DataObject\LeadManipulator;
use Mautic\LeadBundle\Deduplicate\ContactMerger;
use Mautic\LeadBundle\Deduplicate\Exception\SameContactException;
use Mautic\LeadBundle\Entity\Lead;
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
     * @var Lead|null
     */
    private $trackedContact;

    private array $queryFields = [];

    private array $publiclyUpdatableFieldValues = [];

    public function __construct(
        private LeadModel $leadModel,
        private ContactTracker $contactTracker,
        private CoreParametersHelper $coreParametersHelper,
        private IpLookupHelper $ipLookupHelper,
        private RequestStack $requestStack,
        private Logger $logger,
        private EventDispatcherInterface $eventDispatcher,
        private ContactMerger $contactMerger
    ) {
    }

    /**
     * @return Lead|null
     */
    public function getContactFromQuery(array $queryFields = [])
    {
        unset($queryFields['page_url']); // This is set now automatically by PageModel
        $this->queryFields    = $queryFields;

        try {
            $foundContact         = $this->getContactFromUrl();
            $this->trackedContact = $foundContact;
            $this->contactTracker->setTrackedContact($this->trackedContact);
        } catch (ContactNotFoundException) {
        }

        if (!$this->trackedContact) {
            $this->trackedContact = $this->contactTracker->getContact();
        }

        if (!$this->trackedContact) {
            return null;
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
        } elseif ($clickthrough = $this->requestStack->getCurrentRequest()->get('ct', [])) {
            $clickthrough = ClickthroughHelper::decodeArrayFromUrl($clickthrough);
        }

        if (!is_array($clickthrough)) {
            throw new ContactNotFoundException();
        }

        try {
            return $this->getContactFromClickthrough($clickthrough);
        } catch (ContactNotFoundException) {
        }

        $this->setEmailFromClickthroughIdentification($clickthrough);

        /* @var Lead $foundContact */
        if (!empty($this->queryFields)) {
            [$foundContact, $this->publiclyUpdatableFieldValues] = $this->leadModel->checkForDuplicateContact(
                $this->queryFields,
                true,
                true
            );

            if ($this->trackedContact && $this->trackedContact->getId() && $foundContact->getId()) {
                try {
                    $foundContact = $this->contactMerger->merge($this->trackedContact, $foundContact);
                } catch (SameContactException) {
                }
            }

            if (is_null($this->trackedContact) or $foundContact->getId() !== $this->trackedContact->getId()) {
                // A contact was found by a publicly updatable field
                if (!$foundContact->isNew()) {
                    return $foundContact;
                }
            }
        }

        throw new ContactNotFoundException();
    }

    /**
     * Identify a contact through a clickthrough URL.
     *
     * @return Lead
     *
     * @throws ContactNotFoundException
     */
    private function getContactFromClickthrough(array $clickthrough)
    {
        $event = new ContactIdentificationEvent($clickthrough);
        $this->eventDispatcher->dispatch($event, LeadEvents::ON_CLICKTHROUGH_IDENTIFICATION);

        if ($contact = $event->getIdentifiedContact()) {
            $this->logger->debug("LEAD: Contact ID# {$contact->getId()} tracked through clickthrough query by the ".$event->getIdentifier().' channel');

            // Merge tracked visitor into the clickthrough contact
            return $this->mergeWithTrackedContact($contact);
        }

        throw new ContactNotFoundException();
    }

    private function setEmailFromClickthroughIdentification(array $clickthrough): void
    {
        if (!$this->coreParametersHelper->get('track_by_tracking_url') || !empty($queryFields['email'])) {
            return;
        }

        if (empty($clickthrough['lead']) || !$foundContact = $this->leadModel->getEntity($clickthrough['lead'])) {
            return;
        }

        // Identify contact from link if email field is set as publicly updateable
        if ($email = $foundContact->getEmail()) {
            // Add email to query for checkForDuplicateContact to pick up and merge
            $this->queryFields['email'] = $email;
            $this->logger->debug("LEAD: Contact ID# {$clickthrough['lead']} tracked through clickthrough query.");

            return;
        }
    }

    private function prepareContactFromRequest(): void
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
                $this->queryFields['page_url'] ?? ''
            )
        );

        if (isset($this->queryFields['tags'])) {
            $this->leadModel->modifyTags($this->trackedContact, $this->queryFields['tags']);
        }
    }

    /**
     * @return Lead
     */
    private function mergeWithTrackedContact(Lead $foundContact)
    {
        if ($this->trackedContact && $this->trackedContact->getId() && $this->trackedContact->isAnonymous()) {
            try {
                return $this->contactMerger->merge($this->trackedContact, $foundContact);
            } catch (SameContactException) {
            }
        }

        return $foundContact;
    }
}
