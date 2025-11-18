<?php

declare(strict_types=1);

namespace Mautic\ChannelBundle\Tests\Command;

use Mautic\ChannelBundle\Entity\MessageQueue;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\CoreBundle\Tests\Functional\CreateTestEntitiesTrait;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Entity\Stat;
use Mautic\EmailBundle\Entity\StatRepository;
use Mautic\LeadBundle\Entity\Lead;
use PHPUnit\Framework\Assert;

final class ProcessMarketingMessagesQueueCommandFunctionalTest extends MauticMysqlTestCase
{
    use CreateTestEntitiesTrait;

    public function testIdleCommand(): void
    {
        $commandTester = $this->testSymfonyCommand('mautic:messages:send');
        Assert::assertSame(0, $commandTester->getStatusCode());
    }

    public function testCommandWithEmailQueue(): void
    {
        $lead   = $this->createLead('John', 'Doe', 'jd@example.com');
        $email1 = $this->createEmail('Test Email 1');
        $email2 = $this->createEmail('Test Email 2');
        $this->em->flush();

        $scheduledDate = new \DateTime('-10 minutes');
        $datePublished = new \DateTime('-1 day');

        $messages = [
            $this->createMessageQueue($email1, $lead, $scheduledDate, $datePublished),
            $this->createMessageQueue($email2, $lead, $scheduledDate, $datePublished),
        ];

        foreach ($messages as $message) {
            $this->em->persist($message);
        }
        $this->em->flush();

        $commandTester = $this->testSymfonyCommand('mautic:messages:send');
        Assert::assertSame(0, $commandTester->getStatusCode());

        // Check if email stats are created for each email sent
        $this->assertEmailStatCreated($email1, $lead);
        $this->assertEmailStatCreated($email2, $lead);
    }

    private function createMessageQueue(Email $email, Lead $lead, \DateTime $scheduledDate, \DateTime $datePublished): MessageQueue
    {
        $message = new MessageQueue();
        $message->setScheduledDate($scheduledDate);
        $message->setDatePublished($datePublished);
        $message->setChannel('email');
        $message->setChannelId($email->getId());
        $message->setLead($lead);
        $message->setPriority(MessageQueue::PRIORITY_NORMAL);
        $message->setMaxAttempts(3);
        $message->setAttempts(0);
        $message->setStatus(MessageQueue::STATUS_PENDING);

        return $message;
    }

    private function assertEmailStatCreated(Email $email, Lead $lead): void
    {
        /** @var StatRepository $emailStatRepository */
        $emailStatRepository = $this->em->getRepository(Stat::class);

        /** @var Stat|null $emailStat */
        $emailStat = $emailStatRepository->findOneBy([
            'email' => $email->getId(),
            'lead'  => $lead->getId(),
        ]);

        Assert::assertNotNull($emailStat, "Email stat not created for email ID {$email->getId()} and lead ID {$lead->getId()}");
    }
}
