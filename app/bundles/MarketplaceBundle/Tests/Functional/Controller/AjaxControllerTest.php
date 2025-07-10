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
use PHPUnit\Framework\Assert;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

final class AjaxControllerTest extends AbstractMauticTestCase
{
    public function testInstallPackageAction(): void
    {
        $request    = new Request([], [], [], [], [], [], '{"vendor":"mautic","package":"test-plugin-bundle"}');
        $controller = $this->generateController(false);

        $response = $controller->installPackageAction($request);

        Assert::assertSame('{"success":true}', $response->getContent());
        Assert::assertSame(200, $response->getStatusCode());
    }

    public function testRemovePackageAction(): void
    {
        $request    = new Request([], [], [], [], [], [], '{"vendor":"mautic","package":"test-plugin-bundle"}');
        $controller = $this->generateController(true);

        $response = $controller->removePackageAction($request);

        Assert::assertSame('{"success":true}', $response->getContent());
        Assert::assertSame(200, $response->getStatusCode());
    }

    private function generateController(bool $isPackageInstalled): AjaxController
    {
        $composer = $this->createMock(ComposerHelper::class);
        $composer->method('install')->willReturn(new ConsoleOutput(0, 'OK'));
        $composer->method('remove')->willReturn(new ConsoleOutput(0, 'OK'));
        $composer->method('isInstalled')->willReturn($isPackageInstalled);

        $cacheHelper = $this->createMock(CacheHelper::class);
        $cacheHelper->method('clearSymfonyCache')->willReturn(0);

        $logger               = $this->createMock(LoggerInterface::class);
        $doctrine             = $this->createMock(ManagerRegistry::class);
        $modelFactory         = $this->createMock(ModelFactory::class);
        $userHelper           = $this->createMock(UserHelper::class);
        $coreParametersHelper = $this->createMock(CoreParametersHelper::class);
        $dispatcher           = $this->createMock(EventDispatcherInterface::class);
        $translator           = $this->createMock(Translator::class);
        $flashBag             = $this->createMock(FlashBag::class);
        $requestStack         = new RequestStack();
        $security             = $this->createMock(CorePermissions::class);

        $controller = new AjaxController(
            $composer,
            $cacheHelper,
            $logger,
            $doctrine,
            $modelFactory,
            $userHelper,
            $coreParametersHelper,
            $dispatcher,
            $translator,
            $flashBag,
            $requestStack,
            $security
        );
        $controller->setContainer(static::getContainer());

        return $controller;
    }
}
