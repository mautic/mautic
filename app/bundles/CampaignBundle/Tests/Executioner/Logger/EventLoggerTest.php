<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CampaignBundle\Tests\Executioner\Logger;

use Doctrine\Common\Collections\ArrayCollection;
use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\Entity\LeadEventLog;
use Mautic\CampaignBundle\Entity\LeadEventLogRepository;
use Mautic\CampaignBundle\Entity\LeadRepository;
use Mautic\CampaignBundle\Executioner\Logger\EventLogger;
use Mautic\CoreBundle\Entity\IpAddress;
use Mautic\CoreBundle\Helper\IpLookupHelper;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Tracker\ContactTracker;

class EventLoggerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var LeadRepository|\PHPUnit\Framework\MockObject\MockObject
     */
    private $ipLookupHelper;

    /**
     * @var ContactTracker|\PHPUnit\Framework\MockObject\MockObject
     */
    private $contactTracker;

    /**
     * @var LeadEventLogRepository|\PHPUnit\Framework\MockObject\MockObject
     */
    private $leadEventLogRepository;

    /**
     * @var LeadRepository|\PHPUnit\Framework\MockObject\MockObject
     */
    private $leadRepository;

    protected function setUp(): void
    {
        $this->ipLookupHelper         = $this->createMock(IpLookupHelper::class);
        $this->contactTracker         = $this->createMock(ContactTracker::class);
        $this->leadEventLogRepository = $this->createMock(LeadEventLogRepository::class);
        $this->leadRepository         = $this->createMock(LeadRepository::class);
    }

    public function testAllLogsAreReturnedWithFinalPersist()
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

        $this->assertEquals($persistedLogs->count(), $logCollection->count());
        $this->assertEquals($logCollection->getValues(), $persistedLogs->getValues());
    }

    public function testBuildLogEntry()
    {
        $this->ipLookupHelper->method('getIpAddress')->willReturn(new IpAddress());

        $this->leadRepository->expects($this->exactly(3))
            ->method('getContactRotations')
            ->willReturnOnConsecutiveCalls([1 => 1], [1 => 2], [1 => 1]);

        $campaign = $this->createMock(Campaign::class);
        $campaign->method('getId')->willReturnOnConsecutiveCalls([1, 1, 2]);

        $event = $this->createMock(Event::class);
        $event->method('getCampaign')->willReturn($campaign);

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

    private function getLogger()
    {
        return new EventLogger(
            $this->ipLookupHelper,
            $this->contactTracker,
            $this->leadEventLogRepository,
            $this->leadRepository
        );
    }
}
