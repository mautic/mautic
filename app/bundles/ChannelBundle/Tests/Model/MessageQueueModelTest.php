<?php

namespace Mautic\ChannelBundle\Tests\Model;

use Doctrine\ORM\EntityManagerInterface;
use Mautic\ChannelBundle\Entity\MessageQueue;
use Mautic\ChannelBundle\Model\MessageQueueModel;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\LeadBundle\Model\CompanyModel;
use Mautic\LeadBundle\Model\LeadModel;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class MessageQueueModelTest extends \PHPUnit\Framework\TestCase
{
    /** @var string */
    public const DATE = '2019-07-07 15:00:00';

    /** @var MessageQueueModel */
    protected $messageQueue;

    /** @var MessageQueue */
    protected $message;

    protected function setUp(): void
    {
        $lead       = $this->createMock(LeadModel::class);
        $company    = $this->createMock(CompanyModel::class);
        $coreHelper = $this->createMock(CoreParametersHelper::class);

        $this->messageQueue = new MessageQueueModel(
            $lead,
            $company,
            $coreHelper,
            $this->createMock(EntityManagerInterface::class),
            $this->createMock(CorePermissions::class),
            $this->createMock(EventDispatcherInterface::class),
            $this->createMock(UrlGeneratorInterface::class),
            $this->createMock(Translator::class),
            $this->createMock(UserHelper::class),
            $this->createMock(LoggerInterface::class)
        );

        $message      = new MessageQueue();
        $scheduleDate = new \DateTime(self::DATE);
        $message->setScheduledDate($scheduleDate);

        $this->message = $message;
    }

    public function testRescheduleMessageIntervalDay()
    {
        $interval = new \DateInterval('P2D');
        $this->prepareRescheduleMessageIntervalTest($interval);
    }

    public function testRescheduleMessageIntervalWeek()
    {
        $interval = new \DateInterval('P4W');
        $this->prepareRescheduleMessageIntervalTest($interval);
    }

    public function testRescheduleMessageIntervalMonth()
    {
        $interval = new \DateInterval('P8M');
        $this->prepareRescheduleMessageIntervalTest($interval);
    }

    public function testRescheduleMessageNoInterval()
    {
        $interval = new \DateInterval('PT0S');
        $this->prepareRescheduleMessageIntervalTest($interval);
    }

    protected function prepareRescheduleMessageIntervalTest(\DateInterval $interval)
    {
        $oldScheduleDate = $this->message->getScheduledDate();
        $this->messageQueue->reschedule($this->message, $interval);
        $scheduleDate = $this->message->getScheduledDate();
        /** @var \DateTime $oldScheduleDate */
        $oldScheduleDate->add($interval);

        $this->assertEquals($oldScheduleDate, $scheduleDate);
        $this->assertNotSame($oldScheduleDate, $scheduleDate);
    }
}
