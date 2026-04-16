<?php

declare(strict_types=1);

namespace Mautic\MarketplaceBundle\Tests\Functional\Service;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\TransferException;
use Mautic\CoreBundle\Event\EntityImportEvent;
use Mautic\CoreBundle\Event\EntityImportUndoEvent;
use Mautic\CoreBundle\Helper\PathsHelper;
use Mautic\CoreBundle\Test\AbstractMauticTestCase;
use Mautic\MarketplaceBundle\Api\Connection;
use Mautic\MarketplaceBundle\Service\ResourceInstaller;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Filesystem\Filesystem;

final class ResourceInstallerTest extends AbstractMauticTestCase
{
    private MockObject&Connection $marketplaceConnection;
    private MockObject&ClientInterface $httpClient;
    private MockObject&PathsHelper $pathsHelper;
    private MockObject&EventDispatcherInterface $dispatcher;
    private MockObject&LoggerInterface $logger;

    private string $tmpRoot;
    private string $importDir;

    private ResourceInstaller $installer;

    public function setUp(): void
    {
        parent::setUp();

        $this->tmpRoot   = sys_get_temp_dir().'/mautic_resource_installer_'.bin2hex(random_bytes(4));
        $this->importDir = $this->tmpRoot.'/import';

        (new Filesystem())->mkdir([$this->tmpRoot, $this->tmpRoot.'/var']);

        $this->marketplaceConnection  = $this->createMock(Connection::class);
        $this->httpClient             = $this->createMock(ClientInterface::class);
        $this->pathsHelper            = $this->createMock(PathsHelper::class);
        $this->dispatcher             = $this->createMock(EventDispatcherInterface::class);
        $this->logger                 = $this->createMock(LoggerInterface::class);

        $this->pathsHelper->method('getImportCampaignsPath')->willReturn($this->importDir);
        $this->pathsHelper->method('getSystemPath')->with('root')->willReturn($this->tmpRoot);

        $this->installer = new ResourceInstaller(
            $this->marketplaceConnection,
            $this->httpClient,
            $this->pathsHelper,
            $this->dispatcher,
            $this->logger,
        );
    }

    public function tearDown(): void
    {
        (new Filesystem())->remove($this->tmpRoot);
        parent::tearDown();
    }

    public function testIsInstalledReturnsFalseWhenStateFileIsMissing(): void
    {
        Assert::assertFalse($this->installer->isInstalled('vendor/pkg'));
    }

    public function testIsInstalledReturnsTrueForPreviouslyInstalledPackage(): void
    {
        $this->writeInstalledState(['vendor/pkg' => []]);

        Assert::assertTrue($this->installer->isInstalled('vendor/pkg'));
    }

    public function testIsInstalledMigratesLegacyFlatArrayFormat(): void
    {
        $this->writeInstalledState(['vendor/pkg']);

        Assert::assertTrue($this->installer->isInstalled('vendor/pkg'));
    }

    public function testInstallReturnsErrorWhenPackageHasNoDownloadableVersion(): void
    {
        $this->marketplaceConnection->method('getPackage')->willReturn([
            'package' => ['versions' => []],
        ]);

        $result = $this->installer->install('vendor/pkg', 1);

        Assert::assertFalse($result['success']);
        Assert::assertStringContainsString('No downloadable version', $result['errors'][0]);
    }

    public function testInstallReturnsErrorWhenDownloadThrows(): void
    {
        $this->mockPackageWithDistUrl('https://example.test/pkg.zip');

        $this->httpClient->method('request')
            ->willThrowException(new TransferException('connection refused'));

        $result = $this->installer->install('vendor/pkg', 1);

        Assert::assertFalse($result['success']);
        Assert::assertStringContainsString('Failed to download', $result['errors'][0]);
    }

    public function testInstallReturnsErrorWhenDownloadYieldsEmptyFile(): void
    {
        $this->mockPackageWithDistUrl('https://example.test/pkg.zip');

        $this->httpClient->method('request')
            ->willReturnCallback(function (string $method, string $url, array $options) {
                file_put_contents($options['sink'], '');

                return $this->successfulResponse();
            });

        $result = $this->installer->install('vendor/pkg', 1);

        Assert::assertFalse($result['success']);
        Assert::assertStringContainsString('Failed to download', $result['errors'][0]);
    }

    public function testInstallReturnsErrorWhenZipContainsNoCampaignJson(): void
    {
        $this->mockPackageWithDistUrl('https://example.test/pkg.zip');
        $zipContents = $this->buildZip(['composer.json' => '{"name":"vendor/pkg"}']);

        $this->httpClient->method('request')
            ->willReturnCallback(function (string $method, string $url, array $options) use ($zipContents) {
                file_put_contents($options['sink'], $zipContents);

                return $this->successfulResponse();
            });

        $result = $this->installer->install('vendor/pkg', 1);

        Assert::assertFalse($result['success']);
        Assert::assertStringContainsString('Import failed', $result['errors'][0]);
    }

    public function testInstallDispatchesImportEventAndMarksPackageInstalled(): void
    {
        $this->mockPackageWithDistUrl('https://example.test/pkg.zip');

        $campaignJson = json_encode([
            'campaign'       => [['id' => 1, 'name' => 'Test campaign']],
            'campaign_event' => [],
            'lists'          => [],
        ]);
        $zipContents = $this->buildZip(['campaign.json' => $campaignJson]);

        $this->httpClient->method('request')
            ->willReturnCallback(function (string $method, string $url, array $options) use ($zipContents) {
                file_put_contents($options['sink'], $zipContents);

                return $this->successfulResponse();
            });

        $this->dispatcher->expects($this->once())
            ->method('dispatch')
            ->willReturnCallback(function (EntityImportEvent $event) {
                $event->setStatus('imported', ['campaign' => ['ids' => [1], 'names' => ['Test'], 'count' => 1]]);

                return $event;
            });

        $result = $this->installer->install('vendor/pkg', 1);

        Assert::assertTrue($result['success']);
        Assert::assertNotEmpty($result['summary']);
        Assert::assertTrue($this->installer->isInstalled('vendor/pkg'));
    }

    public function testUninstallIsNoOpWhenPackageWasNeverInstalled(): void
    {
        $this->dispatcher->expects($this->never())->method('dispatch');

        $this->installer->uninstall('vendor/pkg');
    }

    public function testUninstallDispatchesUndoEventsAndRemovesPackageFromState(): void
    {
        $summary = [
            [
                'imported' => [
                    'campaign' => ['ids' => [1], 'names' => ['Test'], 'count' => 1],
                ],
            ],
        ];
        $this->writeInstalledState(['vendor/pkg' => $summary]);

        $this->dispatcher->expects($this->once())
            ->method('dispatch')
            ->with($this->isInstanceOf(EntityImportUndoEvent::class));

        $this->installer->uninstall('vendor/pkg');

        Assert::assertFalse($this->installer->isInstalled('vendor/pkg'));
    }

    /**
     * @param array<string, string> $files
     */
    private function buildZip(array $files): string
    {
        $path = $this->tmpRoot.'/fixture_'.bin2hex(random_bytes(4)).'.zip';
        $zip  = new \ZipArchive();
        Assert::assertTrue(true === $zip->open($path, \ZipArchive::CREATE));

        foreach ($files as $name => $content) {
            $zip->addFromString($name, $content);
        }
        $zip->close();

        $contents = (string) file_get_contents($path);
        @unlink($path);

        return $contents;
    }

    private function successfulResponse(): ResponseInterface
    {
        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn(200);

        return $response;
    }

    private function mockPackageWithDistUrl(string $url): void
    {
        $this->marketplaceConnection->method('getPackage')->willReturn([
            'package' => [
                'versions' => [
                    '1.0.0' => [
                        'version' => '1.0.0',
                        'dist'    => ['url' => $url],
                    ],
                ],
            ],
        ]);
    }

    /**
     * @param array<int|string, mixed> $data
     */
    private function writeInstalledState(array $data): void
    {
        file_put_contents($this->tmpRoot.'/var/marketplace_installed_resources.json', json_encode($data));
    }
}
