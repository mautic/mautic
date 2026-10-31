<?php

declare(strict_types=1);

namespace Mautic\ChannelBundle\Tests\Model;

use Doctrine\ORM\EntityManagerInterface;
use Mautic\ChannelBundle\Entity\MessageQueue;
use Mautic\ChannelBundle\Entity\MessageQueueRepository;
use Mautic\ChannelBundle\Model\MessageQueueModel;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\LeadBundle\Entity\FrequencyRuleRepository;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadRepository;
use Mautic\LeadBundle\Model\CompanyModel;
use Mautic\LeadBundle\Model\LeadModel;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class MessageQueueModelTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var string
     */
    public const DATE = '2019-07-07 15:00:00';

    private MessageQueueModel $messageQueue;

    private MessageQueue $message;

    /**
     * @var MockObject&EntityManagerInterface
     */
    private MockObject $entityManager;

    /**
     * @var MockObject&MessageQueueRepository
     */
    private MockObject $messageQueueRepository;

    /**
     * @var MockObject&LeadRepository
     */
    private MockObject $leadRepository;

    protected function setUp(): void
    {
        $this->entityManager          = $this->createMock(EntityManagerInterface::class);
        $this->messageQueueRepository = $this->createMock(MessageQueueRepository::class);
        $this->leadRepository         = $this->createMock(LeadRepository::class);

        $this->messageQueue = new MessageQueueModel(
            $this->entityManager,
            $this->createStub(CorePermissions::class),
            $this->createStub(EventDispatcherInterface::class),
            $this->createStub(UrlGeneratorInterface::class),
            $this->createStub(Translator::class),
            $this->createStub(UserHelper::class),
            $this->createStub(LoggerInterface::class),
            $this->createStub(CoreParametersHelper::class),
        );
        $this->messageQueue->autowireMessageQueueModel(
            $this->createStub(LeadModel::class),
            $this->createStub(CompanyModel::class),
            $this->messageQueueRepository,
            $this->createStub(FrequencyRuleRepository::class),
            $this->leadRepository
        );

        $message      = new MessageQueue();
        $scheduleDate = new \DateTime(self::DATE);
        $message->setScheduledDate($scheduleDate);

        $this->message = $message;
    }

    public function testRescheduleMessageIntervalDay(): void
    {
        $interval = new \DateInterval('P2D');
        $this->prepareRescheduleMessageIntervalTest($interval);
    }

    public function testRescheduleMessageIntervalWeek(): void
    {
        $interval = new \DateInterval('P4W');
        $this->prepareRescheduleMessageIntervalTest($interval);
    }

    public function testRescheduleMessageIntervalMonth(): void
    {
        $interval = new \DateInterval('P8M');
        $this->prepareRescheduleMessageIntervalTest($interval);
    }

    public function testRescheduleMessageNoInterval(): void
    {
        $interval = new \DateInterval('PT0S');
        $this->prepareRescheduleMessageIntervalTest($interval);
    }

    protected function prepareRescheduleMessageIntervalTest(\DateInterval $interval): void
    {
        $oldScheduleDate = $this->message->getScheduledDate();
        $this->messageQueue->reschedule($this->message, $interval);
        $scheduleDate = $this->message->getScheduledDate();
        /** @var \DateTime $oldScheduleDate */
        $oldScheduleDate->add($interval);

        $this->assertEquals($oldScheduleDate, $scheduleDate);
        $this->assertNotSame($oldScheduleDate, $scheduleDate);
    }

    public function testSendMessagesWithNullEvent(): void
    {
        $lead  = new Lead();
        $lead->setId(1);
        $this->message->setLead($lead);

        $contactData = [
            1 => [
                'firstname' => 'John',
                'email'     => 'john.doe@example.com',
            ],
        ];

        $this->leadRepository->method('getContacts')->willReturn($contactData);

        $this->entityManager->expects($this->exactly(1))
            ->method('detach');

        $this->messageQueueRepository->method('getQueuedMessages')
            ->willReturn([$this->message]);

        $this->messageQueue->sendMessages('email', 1);
    }

    public function testProcessMessageQueueLeadFieldsShouldNotContainCompany(): void
    {
        $lead = new Lead();
        $lead->setId(1);
        $this->message->setLead($lead);

        $contactData = [
            1 => [
                'firstname' => 'John',
                'email'     => 'john.doe@example.com',
            ],
        ];

        $this->leadRepository->method('getContacts')->willReturn($contactData);

        $this->messageQueue->processMessageQueue($this->message);
        $this->assertArrayNotHasKey('companies', $this->message->getLead()->getFields());
    }
}
