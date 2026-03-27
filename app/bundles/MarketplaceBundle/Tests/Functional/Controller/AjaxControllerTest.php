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
use Mautic\MarketplaceBundle\DTO\PackageDetail;
use Mautic\MarketplaceBundle\Model\PackageModel;
use Mautic\MarketplaceBundle\Security\Permissions\MarketplacePermissions;
use Mautic\MarketplaceBundle\Service\Config;
use Mautic\MarketplaceBundle\Service\ResourceInstaller;
use Mautic\UserBundle\Entity\User;
use PHPUnit\Framework\Assert;
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

    /**
     * @var MockObject|PackageModel
     */
    private MockObject $packageModel;

    /**
     * @var MockObject|ResourceInstaller
     */
    private MockObject $resourceInstaller;

    public function testInstallPackageAction(): void
    {
        $request    = new Request([], [], [], [], [], [], '{"vendor":"mautic","package":"test-plugin-bundle"}');
        $controller = $this->generateController(false);

        $this->packageModel->method('getPackageDetail')
            ->with('mautic/test-plugin-bundle')
            ->willReturn($this->getPluginPackageDetail());

        $this->marketplaceConfig->method('marketplaceIsEnabled')->willReturn(true);
        $this->marketplaceConfig->method('isComposerEnabled')->willReturn(true);
        $this->security->expects($this->any())
            ->method('isGranted')
            ->with(MarketplacePermissions::CAN_INSTALL_PACKAGES)
            ->willReturn(true);

        $response = $controller->installPackageAction($request);

        Assert::assertSame('{"success":true}', $response->getContent());
        Assert::assertSame(200, $response->getStatusCode());
    }

    public function testRemovePackageAction(): void
    {
        $request    = new Request([], [], [], [], [], [], '{"vendor":"mautic","package":"test-plugin-bundle"}');
        $controller = $this->generateController(true);

        $this->packageModel->method('getPackageDetail')
            ->with('mautic/test-plugin-bundle')
            ->willReturn($this->getPluginPackageDetail());

        $this->marketplaceConfig->method('marketplaceIsEnabled')->willReturn(true);
        $this->marketplaceConfig->method('isComposerEnabled')->willReturn(true);
        $this->security->expects($this->any())
            ->method('isGranted')
            ->with(MarketplacePermissions::CAN_REMOVE_PACKAGES)
            ->willReturn(true);

        $response = $controller->removePackageAction($request);

        Assert::assertSame('{"success":true}', $response->getContent());
        Assert::assertSame(200, $response->getStatusCode());
    }

    public function testInstallResourcePackageAction(): void
    {
        $request    = new Request([], [], [], [], [], [], '{"vendor":"vukovicpredrag","package":"mautic-test-campaign-template"}');
        $controller = $this->generateController(false);

        $this->packageModel->method('getPackageDetail')
            ->with('vukovicpredrag/mautic-test-campaign-template')
            ->willReturn($this->getResourcePackageDetail());

        $this->resourceInstaller->method('isInstalled')
            ->with('vukovicpredrag/mautic-test-campaign-template')
            ->willReturn(false);

        $this->resourceInstaller->method('install')
            ->willReturn(['success' => true, 'summary' => [], 'errors' => []]);

        $this->marketplaceConfig->method('marketplaceIsEnabled')->willReturn(true);
        $this->security->expects($this->any())
            ->method('isGranted')
            ->with(MarketplacePermissions::CAN_INSTALL_PACKAGES)
            ->willReturn(true);

        $response = $controller->installPackageAction($request);

        Assert::assertSame('{"success":true}', $response->getContent());
        Assert::assertSame(200, $response->getStatusCode());
    }

    public function testInstallResourcePackageAlreadyInstalled(): void
    {
        $request    = new Request([], [], [], [], [], [], '{"vendor":"vukovicpredrag","package":"mautic-test-campaign-template"}');
        $controller = $this->generateController(false);

        $this->packageModel->method('getPackageDetail')
            ->with('vukovicpredrag/mautic-test-campaign-template')
            ->willReturn($this->getResourcePackageDetail());

        $this->resourceInstaller->method('isInstalled')
            ->with('vukovicpredrag/mautic-test-campaign-template')
            ->willReturn(true);

        $this->marketplaceConfig->method('marketplaceIsEnabled')->willReturn(true);
        $this->security->expects($this->any())
            ->method('isGranted')
            ->with(MarketplacePermissions::CAN_INSTALL_PACKAGES)
            ->willReturn(true);

        $response = $controller->installPackageAction($request);

        Assert::assertSame(400, $response->getStatusCode());
    }

    public function testRemoveResourcePackageAction(): void
    {
        $request    = new Request([], [], [], [], [], [], '{"vendor":"vukovicpredrag","package":"mautic-test-campaign-template"}');
        $controller = $this->generateController(false);

        $this->packageModel->method('getPackageDetail')
            ->with('vukovicpredrag/mautic-test-campaign-template')
            ->willReturn($this->getResourcePackageDetail());

        $this->resourceInstaller->method('isInstalled')
            ->with('vukovicpredrag/mautic-test-campaign-template')
            ->willReturn(true);

        $this->marketplaceConfig->method('marketplaceIsEnabled')->willReturn(true);
        $this->security->expects($this->any())
            ->method('isGranted')
            ->with(MarketplacePermissions::CAN_REMOVE_PACKAGES)
            ->willReturn(true);

        $response = $controller->removePackageAction($request);

        Assert::assertSame('{"success":true}', $response->getContent());
        Assert::assertSame(200, $response->getStatusCode());
    }

    public function testRemoveResourcePackageNotInstalled(): void
    {
        $request    = new Request([], [], [], [], [], [], '{"vendor":"vukovicpredrag","package":"mautic-test-campaign-template"}');
        $controller = $this->generateController(false);

        $this->packageModel->method('getPackageDetail')
            ->with('vukovicpredrag/mautic-test-campaign-template')
            ->willReturn($this->getResourcePackageDetail());

        $this->resourceInstaller->method('isInstalled')
            ->with('vukovicpredrag/mautic-test-campaign-template')
            ->willReturn(false);

        $this->marketplaceConfig->method('marketplaceIsEnabled')->willReturn(true);
        $this->security->expects($this->any())
            ->method('isGranted')
            ->with(MarketplacePermissions::CAN_REMOVE_PACKAGES)
            ->willReturn(true);

        $response = $controller->removePackageAction($request);

        Assert::assertSame(400, $response->getStatusCode());
    }

    private function generateController(bool $isPackageInstalled): AjaxController
    {
        $composer = $this->createMock(ComposerHelper::class);
        $composer->method('install')->willReturn(new ConsoleOutput(0, 'OK'));
        $composer->method('remove')->willReturn(new ConsoleOutput(0, 'OK'));
        $composer->method('isInstalled')->willReturn($isPackageInstalled);

        $cacheHelper = $this->createMock(CacheHelper::class);
        $cacheHelper->method('clearSymfonyCache')->willReturn(0);

        $logger                  = $this->createMock(LoggerInterface::class);
        $doctrine                = $this->createMock(ManagerRegistry::class);
        $modelFactory            = $this->createMock(ModelFactory::class);
        $userHelper              = $this->createMock(UserHelper::class);
        $user                    = $this->createMock(User::class);
        $user->method('getId')->willReturn(1);
        $userHelper->method('getUser')->willReturn($user);
        $coreParametersHelper    = $this->createMock(CoreParametersHelper::class);
        $dispatcher              = $this->createMock(EventDispatcherInterface::class);
        $translator              = $this->createMock(Translator::class);
        $flashBag                = $this->createMock(FlashBag::class);
        $this->requestStack      = $this->createMock(RequestStack::class);
        $this->security          = $this->createMock(CorePermissions::class);
        $this->marketplaceConfig = $this->createMock(Config::class);
        $this->resourceInstaller = $this->createMock(ResourceInstaller::class);
        $this->packageModel      = $this->createMock(PackageModel::class);

        $controller = new AjaxController(
            $composer,
            $cacheHelper,
            $logger,
            $this->marketplaceConfig,
            $this->resourceInstaller,
            $this->packageModel,
            $doctrine,
            $modelFactory,
            $userHelper,
            $coreParametersHelper,
            $dispatcher,
            $translator,
            $flashBag,
            $this->requestStack,
            $this->security
        );
        $controller->setContainer(static::getContainer());

        return $controller;
    }

    private function getPluginPackageDetail(): PackageDetail
    {
        $payload = json_decode(file_get_contents(__DIR__.'/../../ApiResponse/detail.json'), true);

        return PackageDetail::fromArray($payload['package']);
    }

    private function getResourcePackageDetail(): PackageDetail
    {
        $payload = json_decode(file_get_contents(__DIR__.'/../../ApiResponse/detail_resource.json'), true);

        return PackageDetail::fromArray($payload['package']);
    }
}
