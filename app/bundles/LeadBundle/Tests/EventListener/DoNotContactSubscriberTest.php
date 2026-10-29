<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\EventListener;

use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Event\DoNotContactAddEvent;
use Mautic\LeadBundle\Event\DoNotContactRemoveEvent;
use Mautic\LeadBundle\EventListener\DoNotContactSubscriber;
use Mautic\LeadBundle\Model\DoNotContact;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
final class DoNotContactSubscriberTest extends TestCase
{
    private DoNotContactSubscriber $doNotContactSubscriber;

    /**
     * @var MockObject&DoNotContact
     */
    private MockObject $doNotContact;

    protected function setUp(): void
    {
        $this->doNotContact               = $this->createMock(DoNotContact::class);
        $this->doNotContactSubscriber     = new DoNotContactSubscriber($this->doNotContact);
    }

    public function testGetSubscribedEvents(): void
    {
        $this->assertSame(
            [
                DoNotContactAddEvent::class       => ['addDncForLead', 0],
                DoNotContactRemoveEvent::class => ['removeDncForLead', 0],
            ],
            $this->doNotContactSubscriber->getSubscribedEvents()
        );
    }

    public function testAddDncForLeadForNewContacts(): void
    {
        $lead              = new Lead();
        $doNotContactEvent = new DoNotContactAddEvent($lead, 'email');

        $this->doNotContact->expects($this->once())->method('createDncRecord');
        $this->doNotContact->expects($this->never())->method('addDncForContact');

        $this->doNotContactSubscriber->addDncForLead($doNotContactEvent);
    }

    public function testAddDncForLeadForExistedContacts(): void
    {
        $lead = new Lead();
        $lead->setId(1);
        $doNotContactEvent = new DoNotContactAddEvent($lead, 'email');

        $this->doNotContact->expects($this->never())->method('createDncRecord');
        $this->doNotContact->expects($this->once())->method('addDncForContact');

        $this->doNotContactSubscriber->addDncForLead($doNotContactEvent);
    }
}
