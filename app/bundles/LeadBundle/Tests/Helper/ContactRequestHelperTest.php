<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Helper;

use Doctrine\Common\Collections\ArrayCollection;
use Mautic\CoreBundle\Entity\IpAddress;
use Mautic\CoreBundle\Helper\IpLookupHelper;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Entity\Stat;
use Mautic\EmailBundle\Entity\StatRepository;
use Mautic\EmailBundle\Helper\BotRatioHelper;
use Mautic\LeadBundle\Deduplicate\ContactMerger;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadDevice;
use Mautic\LeadBundle\Entity\LeadRepository;
use Mautic\LeadBundle\Event\ContactIdentificationEvent;
use Mautic\LeadBundle\Helper\ContactRequestHelper;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\LeadBundle\Tracker\ContactTracker;
use Mautic\LeadBundle\Tracker\Service\DeviceTrackingService\DeviceTrackingServiceInterface;
use Monolog\Logger;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

final class ContactRequestHelperTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var MockObject&LeadModel
     */
    private MockObject $leadModel;

    /**
     * @var MockObject&ContactTracker
     */
    private MockObject $contactTracker;

    /**
     * @var MockObject&IpLookupHelper
     */
    private MockObject $ipLookupHelper;

    /**
     * @var MockObject&EventDispatcher
     */
    private MockObject $dispatcher;

    /**
     * @var MockObject&Lead
     */
    private MockObject $trackedContact;

    /**
     * @var MockObject&ContactMerger
     */
    private MockObject $contactMerger;

    /**
     * @var MockObject&DeviceTrackingServiceInterface
     */
    private MockObject $deviceTrackingService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->leadModel                = $this->createMock(LeadModel::class);
        $this->contactTracker           = $this->createMock(ContactTracker::class);
        $this->ipLookupHelper           = $this->createMock(IpLookupHelper::class);
        $this->dispatcher               = $this->createMock(EventDispatcher::class);
        $this->trackedContact           = $this->createMock(Lead::class);
        $this->contactMerger            = $this->createMock(ContactMerger::class);
        $this->deviceTrackingService    = $this->createMock(DeviceTrackingServiceInterface::class);

        $this->trackedContact->method('getId')
            ->willReturn(1);

        $this->trackedContact->method('getIpAddresses')
            ->willReturn(new ArrayCollection());

        $this->contactTracker->method('getContact')
            ->willReturn($this->trackedContact);

        $this->ipLookupHelper->method('getIpAddress')
            ->willReturn(new IpAddress());
    }

    public function testEventDoesNotIdentifyContact(): void
    {
        $query = [
            'ct' => [
                'lead'    => 2,
                'channel' => [
                    'email' => 1,
                ],
                'stat' => 'abc123',
            ],
        ];

        $email = $this->createMock(Email::class);
        $email->method('getId')
            ->willReturn(2);

        $stat = new Stat();
        $stat->setEmail($email);

        $this->contactMerger->expects($this->never())
            ->method('merge');

        $this->leadModel->expects($this->once())
            ->method('checkForDuplicateContact')
            ->willReturn([$this->trackedContact, []]);

        $helper = $this->getContactRequestHelper();
        $this->assertEquals($this->trackedContact->getId(), $helper->getContactFromQuery($query)->getId());
    }

    public function testEventIdentifiesContact(): void
    {
        $query = [
            'ct' => [
                'lead'    => 2,
                'channel' => [
                    'email' => 1,
                ],
                'stat'    => 'abc123',
            ],
        ];

        $contact = new Lead();

        $this->dispatcher->method('dispatch')
            ->willReturnCallback(function (ContactIdentificationEvent $event) use ($contact): ContactIdentificationEvent {
                $event->setIdentifiedContact($contact, 'email');

                return $event;
            });

        $this->contactMerger->expects($this->never())
            ->method('merge');

        $helper       = $this->getContactRequestHelper();
        $foundContact = $helper->getContactFromQuery($query);

        $this->assertSame($foundContact, $contact);
    }

    public function testEventIdentifiesContactAndMergesAnonymousTrackedContact(): void
    {
        $query = [
            'ct' => [
                'lead'    => 2,
                'channel' => [
                    'email' => 1,
                ],
                'stat'    => 'abc123',
            ],
        ];

        $contact          = $this->createContact(2, false);
        $anonymousContact = $this->createContact(3, true);

        $this->dispatcher->method('dispatch')
            ->willReturnCallback(function (ContactIdentificationEvent $event) use ($contact): ContactIdentificationEvent {
                $event->setIdentifiedContact($contact, 'email');

                return $event;
            });

        $this->trackDevice($anonymousContact);

        $this->contactMerger->expects($this->once())
            ->method('merge')
            ->with($contact, $anonymousContact)
            ->willReturn($contact);

        $helper = $this->getContactRequestHelper();

        $this->assertSame($contact, $helper->getContactFromQuery($query));
    }

    public function testAnonymousTrackedContactIsMergedIntoContactFoundByPubliclyUpdatableField(): void
    {
        $foundContact     = $this->createContact(2, false);
        $anonymousContact = $this->createContact(3, true);

        $this->leadModel->expects($this->once())
            ->method('checkForDuplicateContact')
            ->willReturn([$foundContact, ['email' => 'john@example.com']]);

        $this->trackDevice($anonymousContact);

        $this->contactMerger->expects($this->once())
            ->method('merge')
            ->with($foundContact, $anonymousContact)
            ->willReturn($foundContact);

        $this->contactTracker->expects($this->once())
            ->method('setTrackedContact')
            ->with($foundContact);

        $helper = $this->getContactRequestHelper($this->createRequestStack());

        $this->assertSame($foundContact, $helper->getContactFromQuery(['email' => 'john@example.com']));
    }

    public function testIdentifiedTrackedContactIsNotMergedIntoContactFoundByPubliclyUpdatableField(): void
    {
        $foundContact      = $this->createContact(2, false);
        $identifiedContact = $this->createContact(3, false);

        $this->leadModel->expects($this->once())
            ->method('checkForDuplicateContact')
            ->willReturn([$foundContact, ['email' => 'john@example.com']]);

        $this->trackDevice($identifiedContact);

        $this->contactMerger->expects($this->never())
            ->method('merge');

        $helper = $this->getContactRequestHelper($this->createRequestStack());

        $this->assertSame($foundContact, $helper->getContactFromQuery(['email' => 'john@example.com']));
    }

    public function testContactTrackedWithoutDeviceIsNotMergedIntoContactFoundByPubliclyUpdatableField(): void
    {
        $foundContact     = $this->createContact(2, false);
        $anonymousContact = $this->createContact(3, true);

        $this->leadModel->expects($this->once())
            ->method('checkForDuplicateContact')
            ->willReturn([$foundContact, ['email' => 'john@example.com']]);

        // A contact selected through the legacy mtc_id fallback has no tracked device
        $this->contactTracker->method('getContactByTrackedDevice')
            ->willReturn($anonymousContact);

        $this->deviceTrackingService->method('getTrackedDevice')
            ->willReturn(null);

        $this->contactMerger->expects($this->never())
            ->method('merge');

        $helper = $this->getContactRequestHelper($this->createRequestStack());

        $this->assertSame($foundContact, $helper->getContactFromQuery(['email' => 'john@example.com']));
    }

    public function testContactIdentifiedBySocialProfileIsNotMergedIntoContactFoundByPubliclyUpdatableField(): void
    {
        $foundContact  = $this->createContact(2, false);
        $socialContact = new Lead();
        $socialContact->setId(3);

        $repository = $this->createMock(LeadRepository::class);
        $repository->method('getFieldValues')
            ->with(3)
            ->willReturn(['social' => ['twitter' => ['alias' => 'twitter', 'value' => 'johndoe']]]);

        $this->leadModel->method('getRepository')
            ->willReturn($repository);

        $this->leadModel->expects($this->once())
            ->method('checkForDuplicateContact')
            ->willReturn([$foundContact, ['email' => 'john@example.com']]);

        $this->trackDevice($socialContact);

        $this->contactMerger->expects($this->never())
            ->method('merge');

        $helper = $this->getContactRequestHelper($this->createRequestStack());

        $this->assertSame($foundContact, $helper->getContactFromQuery(['email' => 'john@example.com']));
    }

    private function trackDevice(Lead $contact): void
    {
        $device = new LeadDevice();
        $device->setLead($contact);

        $this->deviceTrackingService->method('getTrackedDevice')
            ->willReturn($device);
    }

    /**
     * @return MockObject&Lead
     */
    private function createContact(int $id, bool $anonymous): MockObject
    {
        $contact = $this->createMock(Lead::class);
        $contact->method('getId')
            ->willReturn($id);
        $contact->method('isAnonymous')
            ->willReturn($anonymous);
        $contact->method('isNew')
            ->willReturn(false);
        $contact->method('getIpAddresses')
            ->willReturn(new ArrayCollection());

        return $contact;
    }

    private function createRequestStack(): RequestStack
    {
        $requestStack = new RequestStack();
        $requestStack->push(new Request());

        return $requestStack;
    }

    private function getContactRequestHelper(?RequestStack $requestStack = null): ContactRequestHelper
    {
        return new ContactRequestHelper(
            $this->leadModel,
            $this->contactTracker,
            $this->ipLookupHelper,
            $requestStack ?? $this->createStub(RequestStack::class),
            $this->createStub(Logger::class),
            $this->dispatcher,
            $this->contactMerger,
            $this->createStub(StatRepository::class),
            $this->createStub(BotRatioHelper::class),
            $this->deviceTrackingService
        );
    }
}
