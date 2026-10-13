<?php

namespace Mautic\LeadBundle\Helper;

use Mautic\CoreBundle\Helper\ClickthroughHelper;
use Mautic\CoreBundle\Helper\IpLookupHelper;
use Mautic\EmailBundle\Entity\Stat;
use Mautic\EmailBundle\Entity\StatRepository;
use Mautic\EmailBundle\Helper\BotRatioHelper;
use Mautic\LeadBundle\DataObject\LeadManipulator;
use Mautic\LeadBundle\Deduplicate\ContactMerger;
use Mautic\LeadBundle\Deduplicate\Exception\SameContactException;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadRepository;
use Mautic\LeadBundle\Event\ContactIdentificationEvent;
use Mautic\LeadBundle\Exception\ContactNotFoundException;
use Mautic\LeadBundle\LeadEvents;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\LeadBundle\Tracker\ContactTracker;
use Mautic\LeadBundle\Tracker\Service\DeviceTrackingService\DeviceTrackingServiceInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class ContactRequestHelper
{
    private ?Lead $trackedContact = null;

    private array $queryFields = [];

    private array $publiclyUpdatableFieldValues = [];

    public function __construct(
        private readonly LeadModel $leadModel,
        private readonly ContactTracker $contactTracker,
        private readonly IpLookupHelper $ipLookupHelper,
        private readonly RequestStack $requestStack,
        private readonly LoggerInterface $logger,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly ContactMerger $contactMerger,
        private readonly StatRepository $statRepository,
        private readonly BotRatioHelper $botRatioHelper,
        private readonly ?DeviceTrackingServiceInterface $deviceTrackingService = null,
        private readonly ?LeadRepository $leadRepository = null,
    ) {
        if (null === $deviceTrackingService || null === $leadRepository) {
            trigger_deprecation('mautic/mautic', '7.2.2', 'Not passing $deviceTrackingService and $leadRepository to %s() is deprecated and they will be required in 8.0. Without them the tracked visitor is not merged into the identified contact.', __METHOD__);
        }
    }

    public function getContactFromQuery(array $queryFields = []): ?Lead
    {
        $request = $this->getCurrentRequest();
        if ($request && $request->cookies->get('Blocked-Tracking')) {
            return null;
        }

        $ipAddress = $this->ipLookupHelper->getIpAddress();
        if (!$ipAddress->isTrackable()) {
            return null;
        }

        $dateTime  = new \DateTime();
        $userAgent = $request ? $request->server->get('HTTP_USER_AGENT') : '';
        if (!empty($queryFields['ct'])) {
            $queryFields['ct'] = (is_array($queryFields['ct'])) ? $queryFields['ct'] : ClickthroughHelper::decodeArrayFromUrl($queryFields['ct']);
        }

        if (isset($queryFields['ct']['stat'])) {
            /** @var Stat $stat */
            $stat = $this->statRepository->findOneBy(['trackingHash' => $queryFields['ct']['stat']]);
            if (null !== $stat && $this->botRatioHelper->isHitByBot($stat, $dateTime, $ipAddress, (string) $userAgent)) {
                return null;
            }
        }

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
     * @throws ContactNotFoundException
     */
    private function getContactFromUrl(): Lead
    {
        $request = $this->getCurrentRequest();

        if ($request && $request->cookies->get('Blocked-Tracking')) {
            throw new ContactNotFoundException();
        }

        // Check for a lead requested through clickthrough query parameter
        if (isset($this->queryFields['ct'])) {
            $clickthrough = (is_array($this->queryFields['ct'])) ? $this->queryFields['ct'] : ClickthroughHelper::decodeArrayFromUrl($this->queryFields['ct']);
        } elseif ($request && $clickthrough = $request->get('ct', [])) {
            $clickthrough = ClickthroughHelper::decodeArrayFromUrl($clickthrough);
        }

        if (!is_array($clickthrough)) {
            throw new ContactNotFoundException();
        }

        try {
            return $this->getContactFromClickthrough($clickthrough);
        } catch (ContactNotFoundException) {
        }

        if ([] !== $this->queryFields) {
            /** @var Lead $foundContact */
            [$foundContact, $this->publiclyUpdatableFieldValues] = $this->leadModel->checkForDuplicateContact(
                $this->queryFields,
                true,
                true
            );

            if ($foundContact->getId()) {
                $foundContact = $this->mergeWithTrackedContact($foundContact);
            }

            if (null === $this->trackedContact || $foundContact->getId() !== $this->trackedContact->getId()) {
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
     * @throws ContactNotFoundException
     */
    private function getContactFromClickthrough(array $clickthrough): Lead
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

    private function prepareContactFromRequest(): void
    {
        $ipAddress          = $this->ipLookupHelper->getIpAddress();
        $contactIpAddresses = $this->trackedContact->getIpAddresses();
        if (!$contactIpAddresses->contains($ipAddress)) {
            $this->trackedContact->addIpAddress($ipAddress);
        }

        if ([] !== $this->publiclyUpdatableFieldValues) {
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
     * Merge the anonymous visitor tracked by the device cookie into the identified contact.
     * The tracked contact is not set yet when the request is being identified, so it is looked up by device.
     * Only the device is trusted: the legacy mtc_id fallback is not proof that the request owns the contact.
     */
    private function mergeWithTrackedContact(Lead $foundContact): Lead
    {
        if (null === $this->deviceTrackingService || null === $this->leadRepository) {
            return $foundContact;
        }

        $trackedDevice  = $this->deviceTrackingService->getTrackedDevice();
        $trackedContact = $this->trackedContact ?? $trackedDevice?->getLead();

        if ($trackedContact && $trackedContact->getId() && !$trackedContact->hasFields()) {
            // isAnonymous() reads social profiles from the custom field values
            $trackedContact->setFields($this->leadRepository->getFieldValues($trackedContact->getId()));
        }

        if ($trackedContact && $trackedContact->getId() && $trackedContact->isAnonymous()) {
            try {
                $foundContact = $this->contactMerger->merge($foundContact, $trackedContact);

                // The tracked device still references the merged visitor, which has been deleted
                if ($trackedDevice && $trackedDevice->getLead() === $trackedContact) {
                    $trackedDevice->setLead($foundContact);
                }
            } catch (SameContactException) {
            }
        }

        return $foundContact;
    }

    private function getCurrentRequest(): ?Request
    {
        return $this->requestStack->getCurrentRequest();
    }
}
