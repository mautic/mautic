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
use Mautic\CampaignBundle\Entity\LeadEventLog;
use Mautic\CampaignBundle\Entity\LeadEventLogRepository;
use Mautic\CampaignBundle\Entity\LeadRepository;
use Mautic\CampaignBundle\Executioner\Logger\EventLogger;
use Mautic\CoreBundle\Helper\IpLookupHelper;
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

    protected function setUp()
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
