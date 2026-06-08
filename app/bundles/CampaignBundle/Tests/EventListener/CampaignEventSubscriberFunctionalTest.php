<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Tests\EventListener;

use Mautic\CampaignBundle\CampaignEvents;
use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\Entity\Lead as CampaignLead;
use Mautic\CampaignBundle\Event\NotifyOfUnpublishEvent;
use Mautic\CoreBundle\Entity\Notification;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\EmailBundle\Entity\Email;
use Mautic\LeadBundle\Entity\Lead;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class CampaignEventSubscriberFunctionalTest extends MauticMysqlTestCase
{
    /**
     * @throws \Exception
     */
    public function testCampaignUnPublishSendsOneUserNotification(): void
    {
        // Create 150 contacts
        $contacts = $this->createContacts(150);

        // Create email
        $email    = $this->createEmail();
        $campaign = $this->createCampaign($email->getId(), $contacts);
        $this->em->clear();

        // Schedule the first campaign event.
        $commandTester = $this->testSymfonyCommand('mautic:campaigns:trigger', ['--campaign-id' => $campaign->getId()]);
        $output        = $commandTester->getDisplay();
        self::assertStringContainsString('150 total events were executed', $output);

        // Force the campaign failure events manually
        // Reload campaign to get the event
        $campaign    = $this->em->find(Campaign::class, $campaign->getId());
        $events      = $campaign->getEvents();
        $failedEvent = $events->first();

        /** @var NotifyOfUnpublishEvent $unpublishEvent */
        $unpublishEvent = new NotifyOfUnpublishEvent($failedEvent);
        /** @var EventDispatcherInterface $dispatcher */
        $dispatcher = static::getContainer()->get('event_dispatcher');
        $dispatcher->dispatch($unpublishEvent, CampaignEvents::ON_CAMPAIGN_UNPUBLISH_NOTIFY);

        // Check for notifications - use a more general query
        $notifications = $this->em->getRepository(Notification::class)
            ->findBy(
                [
                    'type'    => 'error',
                    'message' => "{$campaign->getName()} / Send email to user",
                ]
            );
        self::assertCount(1, $notifications);

        // Let's try dispatching the event again and verify that we have a second notification
        // (verifying that notifications aren't deduplicated)
        $dispatcher->dispatch($unpublishEvent, CampaignEvents::ON_CAMPAIGN_UNPUBLISH_NOTIFY);

        // Query for all notifications
        $notifications = $this->em->getRepository(Notification::class)
            ->findBy([
                'type'    => 'error',
                'message' => "{$campaign->getName()} / Send email to user",
            ]);

        // There should be exactly two notifications created (no deduplication)
        self::assertCount(2, $notifications);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function createContacts(int $numberOfContacts): array
    {
        $contacts = [];

        for ($i = 1; $i <= $numberOfContacts; ++$i) {
            $contacts[] = [
                'firstname' => 'John'.$i,
                'email'     => 'john@email.'.$i.'.com',
            ];
        }

        $this->client->request('POST', '/api/contacts/batch/new', $contacts);
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $contacts = $response['contacts'];
        self::assertCount(150, $contacts);
        self::assertSame(
            201,
            $this->client->getResponse()->getStatusCode(),
            $this->client->getResponse()->getContent()
        );

        return $contacts;
    }

    /**
     * @throws \Exception
     */
    private function createEmail(): Email
    {
        $email = new Email();
        $email->setIsPublished(false);
        $email->setDateAdded(new \DateTime());
        $email->setName('Email name');
        $email->setSubject('Email subject');
        $email->setTemplate('Blank');
        $email->setCustomHtml('Hello there!');
        $this->em->persist($email);
        $this->em->flush();

        return $email;
    }

    /**
     * @param array<int, array<string, mixed>> $contacts
     *
     * @throws \Exception
     */
    private function createCampaign(int $emailId, array $contacts): Campaign
    {
        $campaign = new Campaign();
        $campaign->setName('Campaign name');
        $campaign->setIsPublished(true);

        $event = new Event();
        $event->setCampaign($campaign);
        $event->setName('Send email to user');
        $event->setType('email.send.to.user');
        $event->setEventType('action');
        $event->setProperties(
            [
                'to_owner'  => '0',
                'to'        => '',
                'cc'        => '',
                'bcc'       => '',
                'useremail' => ['email' => $emailId],
            ]
        );
        $event->setTriggerInterval(1);
        $event->setTriggerIntervalUnit('d');
        $event->setTriggerMode('immediate');
        $event->setChannel('email');
        $campaign->addEvent(0, $event);

        foreach ($contacts as $key => $contact) {
            $campaignLead = new CampaignLead();
            $campaignLead->setCampaign($campaign);
            $campaignLead->setLead($this->em->find(Lead::class, $contact['id']));
            $campaignLead->setDateAdded(new \DateTime());
            $this->em->persist($campaignLead);
            $campaign->addLead($key, $campaignLead);
        }

        $this->em->persist($campaign);
        $this->em->flush();

        return $campaign;
    }
}
