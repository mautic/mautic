<?php

declare(strict_types=1);

namespace Mautic\UserBundle\Tests\Security\OIDC\Unit\EventListener;

use Mautic\UserBundle\EventListener\OidcScopesSubscriber;
use Mautic\UserBundle\Security\OIDC\RegisterScopesEvent;
use PHPUnit\Framework\TestCase;

final class OidcScopesSubscriberTest extends TestCase
{
    public function testGetSubscribedEvents(): void
    {
        $this->assertEquals([RegisterScopesEvent::class => ['registerScopes', 0]], OidcScopesSubscriber::getSubscribedEvents());
    }

    public function testRegisterScopes(): void
    {
        $event      = new RegisterScopesEvent();
        $subscriber = new OidcScopesSubscriber();

        $subscriber->registerScopes($event);

        $this->assertEquals(['openid', 'email', 'profile'], $event->getScopes());
    }
}
