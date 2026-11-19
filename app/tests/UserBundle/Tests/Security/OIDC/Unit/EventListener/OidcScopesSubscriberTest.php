<?php

declare(strict_types=1);

namespace Mautic\UserBundle\Tests\Security\OIDC\Unit\EventListener;

use Mautic\UserBundle\EventListener\OidcScopesSubscriber;
use Mautic\UserBundle\Security\OIDC\RegisterScopesEvent;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

final class OidcScopesSubscriberTest extends TestCase
{
    public function testRegisterScopesListensToRegisterScopesEvent(): void
    {
        $attributes = new \ReflectionMethod(OidcScopesSubscriber::class, 'registerScopes')->getAttributes(AsEventListener::class);
        $this->assertCount(1, $attributes);

        $listener = $attributes[0]->newInstance();
        $this->assertSame(RegisterScopesEvent::class, $listener->event);
        $this->assertSame(0, $listener->priority);
    }

    public function testRegisterScopes(): void
    {
        $event      = new RegisterScopesEvent();
        $subscriber = new OidcScopesSubscriber();

        $subscriber->registerScopes($event);

        $this->assertEquals(['openid', 'email', 'profile'], $event->getScopes());
    }
}
