<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Functional\EventListener;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\CoreBundle\Tests\Functional\UserEntityTrait;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\PageBundle\Event\UrlTokenReplaceEvent;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

#[Group('database')]
final class OwnerSubscriberFunctionalTest extends MauticMysqlTestCase
{
    use UserEntityTrait;

    private EventDispatcherInterface $dispatcher;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dispatcher = $this->getContainer()->get(EventDispatcherInterface::class);
    }

    public function testUrlTokenReplaceEventReplacesOwnerFieldToken(): void
    {
        $role  = $this->createRole(sprintf('Owner Role %s', uniqid()));
        $owner = $this->createUser(
            sprintf('owner-%s@example.com', uniqid()),
            sprintf('owner-%s', uniqid()),
            'Adrian',
            'Owner',
            $role
        );

        $lead = new Lead();
        $lead->setEmail(sprintf('contact-%s@example.com', uniqid()));
        $lead->setOwner($owner);

        $this->em->persist($lead);
        $this->em->flush();
        $this->em->clear();

        $lead = $this->em->getRepository(Lead::class)->find($lead->getId());
        $this->assertInstanceOf(Lead::class, $lead);

        $event = new UrlTokenReplaceEvent('https://example.mautic/author/{ownerfield=firstname}/', $lead);
        $this->dispatcher->dispatch($event);

        $this->assertSame('https://example.mautic/author/Adrian/', $event->getContent());
    }

    public function testUrlTokenReplaceEventBlanksOwnerFieldTokenWhenOwnerIsMissing(): void
    {
        $lead = new Lead();
        $lead->setEmail(sprintf('contact-%s@example.com', uniqid()));

        $this->em->persist($lead);
        $this->em->flush();
        $this->em->clear();

        $lead = $this->em->getRepository(Lead::class)->find($lead->getId());
        $this->assertInstanceOf(Lead::class, $lead);

        $event = new UrlTokenReplaceEvent('https://example.mautic/author/{ownerfield=firstname}/', $lead);
        $this->dispatcher->dispatch($event);

        $this->assertSame('https://example.mautic/author//', $event->getContent());
    }
}
