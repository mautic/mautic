<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Tests\Functional;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Entity\Stat;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Helper\ContactTrackingPreferenceHelper;
use Mautic\MessengerBundle\Message\EmailHitNotification;
use Mautic\MessengerBundle\MessageHandler\EmailHitNotificationHandler;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\Transport\InMemory\InMemoryTransport;

/**
 * With a queued hit transport, the contact's tracking choice is checked before the open is queued and again when the
 * queued open is processed.
 */
final class EmailOpenTrackingPreferenceAsyncFunctionalTest extends MauticMysqlTestCase
{
    private const HIT_TRANSPORT = 'messenger.transport.hit';

    protected $useCleanupRollback = false;

    protected function setUp(): void
    {
        $this->configParams['messenger_dsn_hit'] = 'in-memory://default';

        parent::setUp();
    }

    public function testOpenOfAContactWhoTurnedTrackingOffIsNotQueuedOrRecordedAfterTheyTurnItBackOn(): void
    {
        [$contact, $stat] = $this->createSentEmail('queued-off@example.com');
        $this->setActivityTracking($contact, false);

        $this->openEmail($stat);

        $this->assertCount(0, $this->getHitTransport()->getSent(), 'nothing about the opted-out open is queued');

        $this->setActivityTracking($contact, true);
        $this->consumeQueuedHits();

        $this->assertOpenNotRecorded($stat);
    }

    public function testQueuedOpenIsNotRecordedWhenTheContactTurnsTrackingOffBeforeItIsProcessed(): void
    {
        [$contact, $stat] = $this->createSentEmail('queued-on@example.com');

        $this->openEmail($stat);

        $sent = $this->getHitTransport()->getSent();
        $this->assertCount(1, $sent, 'the open of a contact who allows tracking is queued');
        $this->assertInstanceOf(EmailHitNotification::class, $sent[0]->getMessage());

        $this->setActivityTracking($contact, false);
        $this->consumeQueuedHits();

        $this->assertOpenNotRecorded($stat);
    }

    public function testQueuedOpenIsRecordedForAContactWhoAllowsTracking(): void
    {
        [, $stat] = $this->createSentEmail('queued-allowed@example.com');

        $this->openEmail($stat);
        $this->consumeQueuedHits();

        $stat = $this->em->find(Stat::class, $stat->getId());
        $this->assertInstanceOf(Stat::class, $stat);
        $this->assertTrue($stat->getIsRead());
    }

    /**
     * @return array{Lead, Stat}
     */
    private function createSentEmail(string $address): array
    {
        $contact = new Lead();
        $contact->setEmail($address);
        $this->em->persist($contact);

        $email = new Email();
        $email->setName('Queued tracking preference');
        $email->setSubject('Queued tracking preference');
        $email->setEmailType('template');
        $email->setIsPublished(true);
        $this->em->persist($email);

        $stat = new Stat();
        $stat->setEmail($email);
        $stat->setLead($contact);
        $stat->setEmailAddress($address);
        $stat->setTrackingHash(substr(md5($address), 0, 20));
        $stat->setDateSent((new \DateTime())->modify('-80 second'));
        $this->em->persist($stat);
        $this->em->flush();

        $this->logoutUser();

        return [$contact, $stat];
    }

    private function setActivityTracking(Lead $contact, bool $allowed): void
    {
        $contact = $this->em->find(Lead::class, $contact->getId());
        $this->assertInstanceOf(Lead::class, $contact);

        self::getContainer()->get(ContactTrackingPreferenceHelper::class)->setActivityTracking($contact, $allowed, 'test');
    }

    private function openEmail(Stat $stat): void
    {
        $this->client->request(Request::METHOD_GET, '/email/'.$stat->getTrackingHash().'.gif', [], [], [
            'HTTP_USER_AGENT' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 14_0) AppleWebKit/605.1.15 (KHTML, like Gecko)',
            'REMOTE_ADDR'     => '203.0.113.80',
        ]);
        $this->assertResponseIsSuccessful('the image is served either way');
        $this->em->clear();
    }

    private function consumeQueuedHits(): void
    {
        $handler = self::getContainer()->get(EmailHitNotificationHandler::class);

        foreach ($this->getHitTransport()->getSent() as $envelope) {
            $message = $envelope->getMessage();
            $this->assertInstanceOf(EmailHitNotification::class, $message);
            $handler($message);
        }

        $this->em->clear();
    }

    private function getHitTransport(): InMemoryTransport
    {
        $transport = self::getContainer()->get(self::HIT_TRANSPORT);
        $this->assertInstanceOf(InMemoryTransport::class, $transport);

        return $transport;
    }

    private function assertOpenNotRecorded(Stat $stat): void
    {
        $stat = $this->em->find(Stat::class, $stat->getId());
        $this->assertInstanceOf(Stat::class, $stat);
        $this->assertFalse($stat->getIsRead(), 'the open is not recorded');
        $this->assertNotInstanceOf(\DateTimeInterface::class, $stat->getLastOpened());
    }
}
