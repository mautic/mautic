<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\EventListener;

use Mautic\DynamicContentBundle\DynamicContentEvents;
use Mautic\DynamicContentBundle\Event\ContactFiltersEvaluateEvent;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadListRepository;
use Mautic\LeadBundle\EventListener\DynamicContentSubscriber;
use Mautic\LeadBundle\Segment\OperatorOptions;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DynamicContentSubscriberTest extends TestCase
{
    /**
     * @var LeadListRepository|MockObject
     */
    private $segmentRepository;

    /**
     * @var DynamicContentSubscriber
     */
    private $subscriber;

    protected function setUp(): void
    {
        $this->segmentRepository = $this->createMock(LeadListRepository::class);
        $this->subscriber        = new DynamicContentSubscriber($this->segmentRepository);

        parent::setUp();
    }

    public function testGetSubscribedEvents(): void
    {
        self::assertSame(
            [
                DynamicContentEvents::ON_CONTACTS_FILTER_EVALUATE => ['onContactFilterEvaluate', 0],
            ],
            DynamicContentSubscriber::getSubscribedEvents()
        );
    }

    public function testOnContactFilterEvaluateUnknownOperator(): void
    {
        $contactId = 1;
        $filters   = [
            [
                'type'     => 'leadlist',
                'operator' => 'unknownFilter',
                'filter'   => null,
            ],
        ];
        $contact = (new Lead())->setId($contactId);

        $event = new ContactFiltersEvaluateEvent($filters, $contact);

        $this->expectException(\InvalidArgumentException::class);

        $this->subscriber->onContactFilterEvaluate($event);
    }

    public function testOnContactFilterEvaluateEmpty(): void
    {
        $contactId = 1;
        $filters   = [
            [
                'type'     => 'leadlist',
                'operator' => OperatorOptions::EMPTY,
                'filter'   => null,
            ],
        ];
        $contact = (new Lead())->setId($contactId);

        $event = new ContactFiltersEvaluateEvent($filters, $contact);

        $this->segmentRepository->expects(self::once())
            ->method('isNotContactInAnySegment')
            ->with($contactId)
            ->willReturn(true);

        $this->subscriber->onContactFilterEvaluate($event);
        self::assertTrue($event->isEvaluated());
        self::assertTrue($event->isMatched());
    }

    public function testOnContactFilterEvaluateNotEmpty(): void
    {
        $contactId = 1;
        $filters   = [
            [
                'type'     => 'leadlist',
                'operator' => OperatorOptions::NOT_EMPTY,
                'filter'   => null,
            ],
        ];
        $contact = (new Lead())->setId($contactId);

        $event = new ContactFiltersEvaluateEvent($filters, $contact);

        $this->segmentRepository->expects(self::once())
            ->method('isContactInAnySegment')
            ->with($contactId)
            ->willReturn(true);

        $this->subscriber->onContactFilterEvaluate($event);
        self::assertTrue($event->isEvaluated());
        self::assertTrue($event->isMatched());
    }

    public function testOnContactFilterEvaluateNotIn(): void
    {
        $contactId = 1;
        $filters   = [
            [
                'type'     => 'leadlist',
                'operator' => OperatorOptions::IN,
                'filter'   => ['something'],
            ],
        ];
        $contact = (new Lead())->setId($contactId);

        $event = new ContactFiltersEvaluateEvent($filters, $contact);

        $this->segmentRepository->expects(self::once())
            ->method('isContactInSegments')
            ->with($contactId, $filters[0]['filter'])
            ->willReturn(true);

        $this->subscriber->onContactFilterEvaluate($event);
        self::assertTrue($event->isEvaluated());
        self::assertTrue($event->isMatched());
    }

    public function testOnContactFilterEvaluateNotNotIn(): void
    {
        $contactId = 1;
        $filters   = [
            [
                'type'     => 'leadlist',
                'operator' => OperatorOptions::NOT_IN,
                'filter'   => ['something'],
            ],
        ];
        $contact = (new Lead())->setId($contactId);

        $event = new ContactFiltersEvaluateEvent($filters, $contact);

        $this->segmentRepository->expects(self::once())
            ->method('isNotContactInSegments')
            ->with($contactId, $filters[0]['filter'])
            ->willReturn(true);

        $this->subscriber->onContactFilterEvaluate($event);
        self::assertTrue($event->isEvaluated());
        self::assertTrue($event->isMatched());
    }
}
