<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Helper;

use Mautic\CoreBundle\Entity\IpAddress;
use Mautic\CoreBundle\Helper\IpLookupHelper;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Entity\Stat;
use Mautic\EmailBundle\Entity\StatRepository;
use Mautic\EmailBundle\Helper\BotRatioHelper;
use Mautic\LeadBundle\Deduplicate\ContactMerger;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Event\ContactIdentificationEvent;
use Mautic\LeadBundle\Helper\ContactRequestHelper;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\LeadBundle\Tracker\ContactTracker;
use Monolog\Logger;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\RequestStack;

#[AllowMockObjectsWithoutExpectations]
final class ContactRequestHelperTest extends TestCase
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

    private Lead $trackedContact;

    /**
     * @var MockObject&ContactMerger
     */
    private MockObject $contactMerger;

    protected function setUp(): void
    {
        parent::setUp();

        $this->leadModel                = $this->createMock(LeadModel::class);
        $this->contactTracker           = $this->createMock(ContactTracker::class);
        $this->ipLookupHelper           = $this->createMock(IpLookupHelper::class);
        $this->dispatcher               = $this->createMock(EventDispatcher::class);
        $this->trackedContact           = new Lead();
        $this->contactMerger            = $this->createMock(ContactMerger::class);

        $this->trackedContact->setId(1);

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

        $email = new Email();
        $email->setId(2);

        $stat = new Stat();
        $stat->setEmail($email);

        $this->contactMerger->expects($this->never())
            ->method('merge');

        $this->leadModel->expects($this->once())
            ->method('checkForDuplicateContact')
            ->willReturn([$this->trackedContact, []]);

        $helper = $this->getContactRequestHelper();
        $this->assertSame($this->trackedContact->getId(), $helper->getContactFromQuery($query)->getId());
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

    private function getContactRequestHelper(): ContactRequestHelper
    {
        return new ContactRequestHelper(
            $this->leadModel,
            $this->contactTracker,
            $this->ipLookupHelper,
            $this->createStub(RequestStack::class),
            $this->createStub(Logger::class),
            $this->dispatcher,
            $this->contactMerger,
            $this->createStub(StatRepository::class),
            $this->createStub(BotRatioHelper::class)
        );
    }
}
