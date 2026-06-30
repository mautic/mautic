<?php

namespace Mautic\CampaignBundle\Tests\Executioner\Logger;

use Doctrine\Common\Collections\ArrayCollection;
use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\Entity\LeadEventLog;
use Mautic\CampaignBundle\Entity\LeadEventLogRepository;
use Mautic\CampaignBundle\Entity\LeadRepository;
use Mautic\CampaignBundle\Executioner\Logger\EventLogger;
use Mautic\CampaignBundle\Model\SummaryModel;
use Mautic\CoreBundle\Entity\IpAddress;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\IpLookupHelper;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Tracker\ContactTracker;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class EventLoggerTest extends TestCase
{
    /**
     * @var MockObject&IpLookupHelper
     */
    private MockObject $ipLookupHelper;

    /**
     * @var ContactTracker|\PHPUnit\Framework\MockObject\Stub
     */
    private \PHPUnit\Framework\MockObject\Stub $contactTracker;

    /**
     * @var MockObject&LeadEventLogRepository
     */
    private MockObject $leadEventLogRepository;

    /**
     * @var MockObject&LeadRepository
     */
    private MockObject $leadRepository;

    /**
     * @var SummaryModel|\PHPUnit\Framework\MockObject\Stub
     */
    private \PHPUnit\Framework\MockObject\Stub $summaryModel;

    /**
     * @var CoreParametersHelper&\PHPUnit\Framework\MockObject\Stub
     */
    private \PHPUnit\Framework\MockObject\Stub $coreParametersHelper;

    protected function setUp(): void
    {
        $this->ipLookupHelper         = $this->createMock(IpLookupHelper::class);
        $this->contactTracker         = $this->createStub(ContactTracker::class);
        $this->leadEventLogRepository = $this->createMock(LeadEventLogRepository::class);
        $this->leadRepository         = $this->createMock(LeadRepository::class);
        $this->summaryModel           = $this->createStub(SummaryModel::class);
        $this->coreParametersHelper   = $this->createStub(CoreParametersHelper::class);
    }

    public function testAllLogsAreReturnedWithFinalPersist(): void
    {
        $logCollection = new ArrayCollection();
        while ($logCollection->count() < 60) {
            $log = $this->createMock(LeadEventLog::class);
            $log->method('getId')
                ->willReturn($logCollection->count() + 1);

            $logCollection->add($log);
        }

        $this->leadEventLogRepository->expects($this->exactly(3))
            ->method('saveEntities');

        $logger = $this->getLogger();
        foreach ($logCollection as $log) {
            $logger->queueToPersist($log);
        }

        $persistedLogs = $logger->persistQueuedLogs();

        $this->assertCount($persistedLogs->count(), $logCollection);
        $this->assertEquals($logCollection->getValues(), $persistedLogs->getValues());
    }

    public function testBuildLogEntry(): void
    {
        $this->ipLookupHelper->method('getIpAddress')->willReturn(new IpAddress());

        $this->leadRepository->expects($this->exactly(3))
            ->method('getContactRotations')
            ->willReturnOnConsecutiveCalls(
                [1 => ['rotation' => 1, 'manually_removed' => 0]],
                [1 => ['rotation' => 2, 'manually_removed' => 0]],
                [1 => ['rotation' => 1, 'manually_removed' => 0]],
            );

        /** @var MockObject&Campaign $campaign */
        $campaign = $this->createMock(Campaign::class);
        $campaign->method('getId')->willReturnOnConsecutiveCalls(1, 1, 1, 1, 2, 2);

        $event = new Event();
        $event->setCampaign($campaign);

        /** @var MockObject&Lead $contact */
        $contact = $this->createMock(Lead::class);
        $contact->method('getId')->willReturn(1);

        // rotation for campaign 1 and contact 1
        $log = $this->getLogger()->buildLogEntry($event, $contact, false);
        $this->assertEquals(1, $log->getRotation());

        // rotation for campaign 1 and contact 1
        $log = $this->getLogger()->buildLogEntry($event, $contact, false);
        $this->assertEquals(2, $log->getRotation());

        // rotation for campaign 2 and contact 1
        $log = $this->getLogger()->buildLogEntry($event, $contact, false);
        $this->assertEquals(1, $log->getRotation());
    }

    private function getLogger(): EventLogger
    {
        return new EventLogger(
            $this->ipLookupHelper,
            $this->contactTracker,
            $this->leadEventLogRepository,
            $this->leadRepository,
            $this->summaryModel,
            $this->coreParametersHelper
        );
    }
}
