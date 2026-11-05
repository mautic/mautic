<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Unit\EventListener;

use Mautic\CoreBundle\EventListener\RouterSubscriber;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouterInterface;

final class RouterSubscriberTest extends TestCase
{
    public function testRouterContextUsesConfiguredSiteUrl(): void
    {
        $context = new RequestContext('/subrequest', 'GET', 'request.example.com', 'http', 80, 443, '/index.php/');
        $router  = $this->createStub(RouterInterface::class);
        $router->method('getContext')->willReturn($context);

        $subscriber = new RouterSubscriber($router, 'https', 'mautic.example.com', '8443', '8080', '/mautic');
        $subscriber->setRouterRequestContext();

        $this->assertSame('/mautic', $context->getBaseUrl());
        $this->assertSame('https', $context->getScheme());
        $this->assertSame('mautic.example.com', $context->getHost());
        $this->assertSame(8080, $context->getHttpPort());
        $this->assertSame(8443, $context->getHttpsPort());
    }

    public function testRouterContextIsReappliedAfterSubrequestFinishes(): void
    {
        $context = new RequestContext('', 'GET', 'request.example.com', 'http');
        $router  = $this->createStub(RouterInterface::class);
        $router->method('getContext')->willReturn($context);

        $subscriber = new RouterSubscriber($router, 'https', 'mautic.example.com', null, null, '');
        $subscriber->setRouterRequestContext();

        // Symfony restores the parent request context when a subrequest finishes.
        $context->setScheme('http');
        $context->setHost('request.example.com');
        $subscriber->setRouterRequestContext();

        $this->assertSame('https', $context->getScheme());
        $this->assertSame('mautic.example.com', $context->getHost());
        $this->assertSame(['setRouterRequestContext', -1], RouterSubscriber::getSubscribedEvents()[KernelEvents::FINISH_REQUEST]);
    }
}
