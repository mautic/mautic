<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Tests\EventListener;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\CoreBundle\Tests\Functional\CreateTestEntitiesTrait;
use Mautic\EmailBundle\EmailEvents;
use Mautic\EmailBundle\Event\EmailSendEvent;
use Mautic\PointBundle\Entity\Point;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

final class PointSubscriberFunctionalTest extends MauticMysqlTestCase
{
    use CreateTestEntitiesTrait;

    /**
     * Test that PointSubscriber gracefully handles a deleted contact during email send.
     * This simulates a race condition where a contact is deleted while an email batch is being sent.
     */
    public function testOnEmailSendWithDeletedContact(): void
    {
        $lead  = $this->createLead('Test Contact', 'User', 'test@example.com');
        $email = $this->createEmail('Test Email');

        // Create a point action for email.send so triggerAction actually loads the Lead proxy
        $point = new Point();
        $point->setName('Email send point');
        $point->setType('email.send');
        $point->setDelta(1);
        $point->isPublished(true);
        $this->em->persist($point);

        $this->em->flush();
        $deletedLeadId = $lead->getId();
        $this->em->remove($lead);
        $this->em->flush();
        $this->em->clear();

        // Create an event with the deleted lead ID
        $leadArray = [
            'id'        => $deletedLeadId,
            'email'     => 'test@example.com',
            'firstname' => 'Test',
            'lastname'  => 'User',
        ];

        $event = new EmailSendEvent(null, ['email' => $email, 'lead' => $leadArray]);

        $dispatcher = self::getContainer()->get('event_dispatcher');
        self::assertInstanceOf(EventDispatcherInterface::class, $dispatcher);
        self::assertSame($deletedLeadId, $event->getLead()['id']);

        $dispatcher->dispatch($event, EmailEvents::EMAIL_ON_SEND);
    }
}
