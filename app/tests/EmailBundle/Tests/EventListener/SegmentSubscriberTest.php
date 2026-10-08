<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Tests\EventListener;

use Mautic\EmailBundle\EventListener\SegmentSubscriber;
use Mautic\EmailBundle\Model\EmailModel;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Event\ListChangeEvent;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class SegmentSubscriberTest extends TestCase
{
    /**
     * @var MockObject&EmailModel
     */
    private MockObject $emailModel;

    private SegmentSubscriber $subscriber;

    protected function setUp(): void
    {
        parent::setUp();

        $this->emailModel  = $this->createMock(EmailModel::class);
        $this->subscriber  = new SegmentSubscriber($this->emailModel);
    }

    public function testOnListChangeInvalidatesPendingCountCacheForList(): void
    {
        $segment = new LeadList();
        $segment->setId(42);

        $this->emailModel->expects($this->once())
            ->method('invalidatePendingCountCacheForList')
            ->with(42);

        $this->subscriber->onListChange(new ListChangeEvent(new Lead(), $segment));
    }
}
