<?php

declare(strict_types=1);

namespace Mautic\MarketplaceBundle\Tests\Functional\Controller;

use Doctrine\Persistence\ManagerRegistry;
use Mautic\CoreBundle\Factory\ModelFactory;
use Mautic\CoreBundle\Helper\CacheHelper;
use Mautic\CoreBundle\Helper\ComposerHelper;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Service\FlashBag;
use Mautic\CoreBundle\Test\AbstractMauticTestCase;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\MarketplaceBundle\Controller\AjaxController;
use Mautic\MarketplaceBundle\DTO\ConsoleOutput;
use Mautic\MarketplaceBundle\Security\Permissions\MarketplacePermissions;
use Mautic\MarketplaceBundle\Service\Config;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

final class AjaxControllerTest extends AbstractMauticTestCase
{
    /**
     * @var MockObject|CorePermissions
     */
    private MockObject $security;

    /**
     * @var MockObject|Config
     */
    private MockObject $marketplaceConfig;

    /**
     * @var MockObject|RequestStack
     */
    private MockObject $requestStack;

    public function testInstallPackageAction(): void
    {
        $request    = new Request([], [], [], [], [], [], '{"vendor":"mautic","package":"test-plugin-bundle"}');
        $controller = $this->generateController(false);

        $this->marketplaceConfig->method('marketplaceIsEnabled')->willReturn(true);
        $this->marketplaceConfig->method('isComposerEnabled')->willReturn(true);
        $this->security
            ->method('isGranted')
            ->with(MarketplacePermissions::CAN_INSTALL_PACKAGES)
            ->willReturn(true);

        $response = $controller->installPackageAction($request);

        $this->assertSame('{"success":true}', $response->getContent());
        $this->assertSame(200, $response->getStatusCode());
    }

    public function testRemovePackageAction(): void
    {
        $request    = new Request([], [], [], [], [], [], '{"vendor":"mautic","package":"test-plugin-bundle"}');
        $controller = $this->generateController(true);

        $this->marketplaceConfig->method('marketplaceIsEnabled')->willReturn(true);
        $this->marketplaceConfig->method('isComposerEnabled')->willReturn(true);
        $this->security
            ->method('isGranted')
            ->with(MarketplacePermissions::CAN_REMOVE_PACKAGES)
            ->willReturn(true);

        $response = $controller->removePackageAction($request);

        $this->assertSame('{"success":true}', $response->getContent());
        $this->assertSame(200, $response->getStatusCode());
    }

    private function generateController(bool $isPackageInstalled): AjaxController
    {
        $composer = $this->createMock(ComposerHelper::class);
        $composer->method('install')->willReturn(new ConsoleOutput(0, 'OK'));
        $composer->method('remove')->willReturn(new ConsoleOutput(0, 'OK'));
        $composer->method('isInstalled')->willReturn($isPackageInstalled);

        $cacheHelper = $this->createMock(CacheHelper::class);
        $cacheHelper->method('clearSymfonyCache')->willReturn(0);
        $this->requestStack      = $this->createMock(RequestStack::class);
        $this->security          = $this->createMock(CorePermissions::class);
        $this->marketplaceConfig = $this->createMock(Config::class);

        $controller = new AjaxController(
            $this->createStub(ManagerRegistry::class),
            $this->createStub(ModelFactory::class),
            $this->createStub(UserHelper::class),
            $this->createStub(CoreParametersHelper::class),
            $this->createStub(EventDispatcherInterface::class),
            $this->createStub(Translator::class),
            $this->createStub(FlashBag::class),
            $this->requestStack,
            $this->security
        );
        $controller->autowireMarketplaceAjaxController(
            $composer,
            $cacheHelper,
            $this->createStub(LoggerInterface::class),
            $this->marketplaceConfig
        );
        $controller->setContainer(static::getContainer());

        return $controller;
    }
}
