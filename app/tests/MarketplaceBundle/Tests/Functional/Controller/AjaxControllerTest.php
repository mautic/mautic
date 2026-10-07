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
use Mautic\MarketplaceBundle\Service\ResourceInstallerInterface;
use Mautic\UserBundle\Entity\User;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;

#[AllowMockObjectsWithoutExpectations]
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

    private MockObject&PackageModel $packageModel;

    private MockObject&ResourceInstallerInterface $resourceInstaller;

    public function testInstallPackageAction(): void
    {
        $request    = new Request([], [], [], [], [], [], '{"vendor":"mautic","package":"test-plugin-bundle"}');
        $controller = $this->generateController(false);

        $this->packageModel->method('getPackageDetail')
            ->willReturn($this->getPluginPackageDetail());

        $this->marketplaceConfig->method('marketplaceIsEnabled')->willReturn(true);
        $this->marketplaceConfig->method('isComposerEnabled')->willReturn(true);
        $this->security
            ->expects($this->once())
            ->method('isGranted')
            ->with(MarketplacePermissions::CAN_INSTALL_PACKAGES)
            ->willReturn(true);

        $response = $controller->installPackageAction($request);

        $this->assertSame('[]', $response->getContent());
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode(), $response->getContent());
    }

    public function testRemovePackageAction(): void
    {
        $request    = new Request([], [], [], [], [], [], '{"vendor":"mautic","package":"test-plugin-bundle"}');
        $controller = $this->generateController(true);

        $this->packageModel->method('getPackageDetail')
            ->willReturn($this->getPluginPackageDetail());

        $this->marketplaceConfig->method('marketplaceIsEnabled')->willReturn(true);
        $this->marketplaceConfig->method('isComposerEnabled')->willReturn(true);
        $this->security
            ->expects($this->once())
            ->method('isGranted')
            ->with(MarketplacePermissions::CAN_REMOVE_PACKAGES)
            ->willReturn(true);

        $response = $controller->removePackageAction($request);

        $this->assertSame('[]', $response->getContent());
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode(), $response->getContent());
    }

    public function testInstallResourcePackageAction(): void
    {
        $request    = new Request([], [], [], [], [], [], '{"vendor":"vukovicpredrag","package":"mautic-test-campaign-template"}');
        $controller = $this->generateController(false);

        $this->packageModel->method('getPackageDetail')
            ->willReturn($this->getResourcePackageDetail());

        $this->resourceInstaller->method('isInstalled')
            ->willReturn(false);

        $this->resourceInstaller->method('install')
            ->willReturn(['success' => true, 'summary' => [], 'errors' => []]);

        $this->marketplaceConfig->method('marketplaceIsEnabled')->willReturn(true);
        $this->security
            ->expects($this->once())
            ->method('isGranted')
            ->with(MarketplacePermissions::CAN_INSTALL_PACKAGES)
            ->willReturn(true);

        $response = $controller->installPackageAction($request);

        $this->assertSame('[]', $response->getContent());
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode(), $response->getContent());
    }

    public function testInstallResourcePackageAlreadyInstalled(): void
    {
        $request    = new Request([], [], [], [], [], [], '{"vendor":"vukovicpredrag","package":"mautic-test-campaign-template"}');
        $controller = $this->generateController(false);

        $this->packageModel->method('getPackageDetail')
            ->willReturn($this->getResourcePackageDetail());

        $this->resourceInstaller->method('isInstalled')
            ->willReturn(true);

        $this->marketplaceConfig->method('marketplaceIsEnabled')->willReturn(true);
        $this->security
            ->expects($this->once())
            ->method('isGranted')
            ->with(MarketplacePermissions::CAN_INSTALL_PACKAGES)
            ->willReturn(true);

        $response = $controller->installPackageAction($request);

        $this->assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode(), (string) $response->getContent());
    }

    public function testRemoveResourcePackageAction(): void
    {
        $request    = new Request([], [], [], [], [], [], '{"vendor":"vukovicpredrag","package":"mautic-test-campaign-template"}');
        $controller = $this->generateController(false);

        $this->packageModel->method('getPackageDetail')
            ->willReturn($this->getResourcePackageDetail());

        $this->resourceInstaller->method('isInstalled')
            ->willReturn(true);

        $this->marketplaceConfig->method('marketplaceIsEnabled')->willReturn(true);
        $this->security
            ->expects($this->once())
            ->method('isGranted')
            ->with(MarketplacePermissions::CAN_REMOVE_PACKAGES)
            ->willReturn(true);

        $response = $controller->removePackageAction($request);

        $this->assertSame('[]', $response->getContent());
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode(), $response->getContent());
    }

    public function testRemoveResourcePackageNotInstalled(): void
    {
        $request    = new Request([], [], [], [], [], [], '{"vendor":"vukovicpredrag","package":"mautic-test-campaign-template"}');
        $controller = $this->generateController(false);

        $this->packageModel->method('getPackageDetail')
            ->willReturn($this->getResourcePackageDetail());

        $this->resourceInstaller->method('isInstalled')
            ->willReturn(false);

        $this->marketplaceConfig->method('marketplaceIsEnabled')->willReturn(true);
        $this->security
            ->expects($this->once())
            ->method('isGranted')
            ->with(MarketplacePermissions::CAN_REMOVE_PACKAGES)
            ->willReturn(true);

        $response = $controller->removePackageAction($request);

        $this->assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode(), (string) $response->getContent());
    }

    private function generateController(bool $isPackageInstalled): AjaxController
    {
        $composer = $this->createMock(ComposerHelper::class);
        $composer->method('install')->willReturn(new ConsoleOutput(0, 'OK'));
        $composer->method('remove')->willReturn(new ConsoleOutput(0, 'OK'));
        $composer->method('isInstalled')->willReturn($isPackageInstalled);

        $cacheHelper = $this->createMock(CacheHelper::class);
        $cacheHelper->method('clearSymfonyCache')->willReturn(0);

        $userHelper = $this->createMock(UserHelper::class);
        $user       = $this->createMock(User::class);
        $user->method('getId')->willReturn(1);
        $userHelper->method('getUser')->willReturn($user);

        $this->requestStack      = $this->createMock(RequestStack::class);
        $this->security          = $this->createMock(CorePermissions::class);
        $this->marketplaceConfig = $this->createMock(Config::class);
        $this->resourceInstaller = $this->createMock(ResourceInstallerInterface::class);
        $this->packageModel      = $this->createMock(PackageModel::class);

        // The controller takes its own dependencies through #[Required] autowiring, so only
        // CommonController's constructor arguments go here.
        $controller = new AjaxController(
            $this->createStub(ManagerRegistry::class),
            $this->createStub(ModelFactory::class),
            $userHelper,
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
            $this->marketplaceConfig,
            $this->resourceInstaller,
            $this->packageModel
        );
        $controller->setContainer(self::getContainer());

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
