<?php

declare(strict_types=1);

namespace Mautic\UserBundle\Tests\Security\OIDC\Unit\EventListener;

use Mautic\UserBundle\Security\OIDC\Event\RegisterScopesEvent;
use Mautic\UserBundle\Security\OIDC\EventListener\RegisterScopesSubscriber;
use PHPUnit\Framework\TestCase;

final class RegisterScopesSubscriberTest extends TestCase
{
    public function testGetSubscribedEvents(): void
    {
        self::assertEquals([RegisterScopesEvent::class => ['registerScopes', 0]], RegisterScopesSubscriber::getSubscribedEvents());
    }

    public function testRegisterScopes(): void
    {
        $event                    = new RegisterScopesEvent();
        $registerScopesSubscriber = new RegisterScopesSubscriber();

        $registerScopesSubscriber->registerScopes($event);

        self::assertEquals(['openid', 'email', 'profile'], $event->getScopes());
    }
}
