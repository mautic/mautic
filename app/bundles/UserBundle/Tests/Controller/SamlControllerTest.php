<?php

declare(strict_types=1);

namespace Mautic\UserBundle\Tests\Controller;

use LightSaml\SpBundle\Controller\DefaultController;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;

final class SamlControllerTest extends TestCase
{
    public function testLoginRedirectsToDiscoveryWhenIdpIsMissing(): void
    {
        $router = $this->createMock(RouterInterface::class);
        $router->expects($this->once())
            ->method('generate')
            ->with('lightsaml_sp.discovery')
            ->willReturn('/s/saml/discovery');

        $container = new Container();
        $container->set('router', $router);
        $container->setParameter('lightsaml_sp.route.discovery', 'lightsaml_sp.discovery');

        $controller = new DefaultController();
        $controller->setContainer($container);

        $response = $controller->loginAction(new Request());

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('/s/saml/discovery', $response->getTargetUrl());
    }
}
