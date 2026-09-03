<?php

declare(strict_types=1);

namespace Mautic\MarketplaceBundle\Tests\Functional\Service;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\TransferException;
use Mautic\CoreBundle\Event\EntityImportEvent;
use Mautic\CoreBundle\Event\EntityImportUndoEvent;
use Mautic\CoreBundle\Helper\ImportHelper;
use Mautic\CoreBundle\Helper\PathsHelper;
use Mautic\CoreBundle\Test\AbstractMauticTestCase;
use Mautic\MarketplaceBundle\Api\Connection;
use Mautic\MarketplaceBundle\Service\ResourceInstaller;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Filesystem\Filesystem;

final class ResourceInstallerTest extends AbstractMauticTestCase
{
    private const string PACKAGE = 'vendor/pkg';

    private MockObject&Connection $marketplaceConnection;

    private MockObject&ClientInterface $httpClient;

    private MockObject&EventDispatcherInterface $dispatcher;

    private string $tmpRoot;

    private ResourceInstaller $installer;

    public function setUp(): void
    {
        parent::setUp();

        $this->tmpRoot   = sys_get_temp_dir().'/mautic_resource_installer_'.bin2hex(random_bytes(4));
        $importDir = $this->tmpRoot.'/import';

        new Filesystem()->mkdir([$this->tmpRoot, $this->tmpRoot.'/var']);

        $this->marketplaceConnection  = $this->createMock(Connection::class);
        $this->httpClient             = $this->createMock(ClientInterface::class);
        $pathsHelper            = $this->createMock(PathsHelper::class);
        $this->dispatcher             = $this->createMock(EventDispatcherInterface::class);

        $pathsHelper->method('getImportCampaignsPath')->willReturn($importDir);
        $pathsHelper->method('getSystemPath')->willReturn($this->tmpRoot);
        $pathsHelper->method('getTemporaryPath')->willReturn($this->tmpRoot.'/tmp');
        $pathsHelper->method('getMediaPath')->willReturn($this->tmpRoot.'/media');

        $this->installer = new ResourceInstaller(
            $this->marketplaceConnection,
            $this->httpClient,
            $pathsHelper,
            $this->dispatcher,
            $this->createStub(LoggerInterface::class),
            new ImportHelper($pathsHelper),
        );
    }

    public function tearDown(): void
    {
        new Filesystem()->remove($this->tmpRoot);
        parent::tearDown();
    }

    public function testIsInstalledReturnsFalseWhenStateFileIsMissing(): void
    {
        $this->assertFalse($this->installer->isInstalled(self::PACKAGE));
    }

    public function testIsInstalledReturnsTrueForPreviouslyInstalledPackage(): void
    {
        $this->writeInstalledState([self::PACKAGE => []]);

        $this->assertTrue($this->installer->isInstalled(self::PACKAGE));
    }

    public function testIsInstalledMigratesLegacyFlatArrayFormat(): void
    {
        $this->writeInstalledState([self::PACKAGE]);

        $this->assertTrue($this->installer->isInstalled(self::PACKAGE));
    }

    public function testInstallReturnsErrorWhenPackageHasNoDownloadableVersion(): void
    {
        $this->marketplaceConnection->method('getPackage')->willReturn([
            'package' => ['versions' => []],
        ]);

        $result = $this->installer->install(self::PACKAGE, 1);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('No downloadable version', $result['errors'][0]);
    }

    /**
     * A dist URL is chosen by whoever published the package, so it must not be able to point the
     * server at its own network or at the local filesystem.
     */
    #[DataProvider('unsafeDistUrlProvider')]
    public function testInstallRefusesUnsafeDistUrls(string $url): void
    {
        $this->mockPackageWithDistUrl($url);

        $this->httpClient->expects($this->never())->method('request');

        $result = $this->installer->install(self::PACKAGE, 1);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Refusing to download', $result['errors'][0]);
    }

    /**
     * @return \Iterator<string, array{string}>
     */
    public static function unsafeDistUrlProvider(): \Iterator
    {
        yield 'plain http' => ['http://example.test/pkg.zip'];
        yield 'file scheme' => ['file:///etc/passwd'];
        yield 'loopback' => ['https://127.0.0.1/pkg.zip'];
        yield 'ipv6 loopback' => ['https://[::1]/pkg.zip'];
        yield 'private range' => ['https://10.1.2.3/pkg.zip'];
        yield 'link local' => ['https://169.254.169.254/latest/meta-data/'];
        yield 'no host' => ['not-a-url'];
    }

    public function testInstallReturnsErrorWhenDownloadThrows(): void
    {
        $this->mockPackageWithDistUrl('https://example.test/pkg.zip');

        $this->httpClient->method('request')
            ->willThrowException(new TransferException('connection refused'));

        $result = $this->installer->install(self::PACKAGE, 1);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Failed to download', $result['errors'][0]);
    }

    /**
     * A malicious/misconfigured dist URL must not be able to fill the server's disk. The
     * on_headers/progress hooks handle real (streaming) Guzzle clients; this test exercises
     * the post-download filesize backstop that catches whatever a mocked/non-conforming
     * client streamed straight to disk regardless of those hooks.
     */
    public function testInstallRejectsAndRemovesOversizedDownload(): void
    {
        $this->mockPackageWithDistUrl('https://example.test/pkg.zip');

        $oversizedContent = str_repeat('a', 52428800 + 1);

        $this->httpClient->method('request')
            ->willReturnCallback(function (string $method, string $url, array $options) use ($oversizedContent): ResponseInterface {
                file_put_contents($options['sink'], $oversizedContent);

                return $this->successfulResponse();
            });

        $result = $this->installer->install(self::PACKAGE, 1);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('exceeds the maximum allowed size', $result['errors'][0]);
        $this->assertFalse($this->installer->isInstalled(self::PACKAGE));

        // The oversized file itself must be removed, not just rejected.
        $importDir = $this->tmpRoot.'/import';
        $this->assertSame([], glob($importDir.'/*') ?: []);
    }

    public function testInstallReturnsErrorWhenDownloadYieldsEmptyFile(): void
    {
        $this->mockPackageWithDistUrl('https://example.test/pkg.zip');

        $this->httpClient->method('request')
            ->willReturnCallback(function (string $method, string $url, array $options): ResponseInterface {
                file_put_contents($options['sink'], '');

                return $this->successfulResponse();
            });

        $result = $this->installer->install(self::PACKAGE, 1);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Failed to download', $result['errors'][0]);
    }

    public function testInstallReturnsErrorWhenZipContainsNoCampaignJson(): void
    {
        $this->mockPackageWithDistUrl('https://example.test/pkg.zip');
        $zipContents = $this->buildZip(['composer.json' => '{"name":"vendor/pkg"}']);

        $this->httpClient->method('request')
            ->willReturnCallback(function (string $method, string $url, array $options) use ($zipContents): ResponseInterface {
                file_put_contents($options['sink'], $zipContents);

                return $this->successfulResponse();
            });

        $result = $this->installer->install(self::PACKAGE, 1);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Import failed', $result['errors'][0]);
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
            ->willReturnCallback(function (string $method, string $url, array $options) use ($zipContents): ResponseInterface {
                file_put_contents($options['sink'], $zipContents);

                return $this->successfulResponse();
            });

        $this->dispatcher->expects($this->once())
            ->method('dispatch')
            ->willReturnCallback(function (EntityImportEvent $event): EntityImportEvent {
                $event->setStatus(EntityImportEvent::NEW, ['campaign' => ['ids' => [1], 'names' => ['Test'], 'count' => 1]]);

                return $event;
            });

        $result = $this->installer->install(self::PACKAGE, 1);

        $this->assertTrue($result['success']);
        $this->assertNotEmpty($result['summary']);
        $this->assertTrue($this->installer->isInstalled(self::PACKAGE));
    }

    public function testInstallRollsBackCreatedEntitiesAndFailsWhenImportReportsErrors(): void
    {
        $this->mockPackageWithDistUrl('https://example.test/pkg.zip');

        $campaignJson = json_encode([
            'campaign'       => [['id' => 1, 'name' => 'Test campaign'], ['id' => 2, 'name' => 'Second campaign']],
            'campaign_event' => [],
            'lists'          => [],
        ]);
        $zipContents = $this->buildZip(['campaign.json' => $campaignJson]);

        $this->httpClient->method('request')
            ->willReturnCallback(function (string $method, string $url, array $options) use ($zipContents): ResponseInterface {
                file_put_contents($options['sink'], $zipContents);

                return $this->successfulResponse();
            });

        // A single EntityImportEvent is dispatched per top-level entity group produced by
        // readZipFile(); this fixture only produces one group, so a subscriber reporting an
        // error is enough to exercise the rollback path.
        $this->dispatcher->expects($this->once())
            ->method('dispatch')
            ->willReturnCallback(function (EntityImportEvent $event): EntityImportEvent {
                $event->setStatus(EntityImportEvent::NEW, ['campaign' => ['ids' => [1], 'names' => ['Test'], 'count' => 1]]);
                $event->setStatus(EntityImportEvent::ERRORS, ['message' => 'Second campaign could not be imported.']);

                return $event;
            });

        $result = $this->installer->install(self::PACKAGE, 1);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Second campaign could not be imported', $result['errors'][0]);
        $this->assertFalse($this->installer->isInstalled(self::PACKAGE), 'A failed install must not be recorded as installed.');
    }

    public function testInstallUndoesEntitiesCreatedBeforeALaterErrorOccurs(): void
    {
        $this->mockPackageWithDistUrl('https://example.test/pkg.zip');
        $zipContents = $this->buildZip(['campaign.json' => json_encode(['campaign' => []])]);

        $this->httpClient->method('request')
            ->willReturnCallback(function (string $method, string $url, array $options) use ($zipContents): ResponseInterface {
                file_put_contents($options['sink'], $zipContents);

                return $this->successfulResponse();
            });

        $this->dispatcher->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(function (EntityImportEvent $event): bool {
                $event->setStatus(EntityImportEvent::NEW, ['campaign' => ['ids' => [1], 'names' => ['Test'], 'count' => 1]]);
                $event->setStatus(EntityImportEvent::ERRORS, ['message' => 'boom']);

                return true;
            }));

        $result = $this->installer->install(self::PACKAGE, 1);

        $this->assertFalse($result['success']);
        $this->assertSame([], $result['summary']);
    }

    public function testInstallRestoresPackagedAssetsToMediaDir(): void
    {
        $this->mockPackageWithDistUrl('https://example.test/pkg.zip');

        $campaignJson = json_encode([
            'campaign'       => [['id' => 1, 'name' => 'Test campaign']],
            'campaign_event' => [],
            'lists'          => [],
        ]);
        $zipContents = $this->buildZip([
            'campaign.json'          => $campaignJson,
            'composer.json'          => '{"name":"vendor/pkg"}',
            'assets/images/logo.png' => 'png-bytes',
        ]);

        $this->httpClient->method('request')
            ->willReturnCallback(function (string $method, string $url, array $options) use ($zipContents): ResponseInterface {
                file_put_contents($options['sink'], $zipContents);

                return $this->successfulResponse();
            });

        $this->dispatcher->method('dispatch')
            ->willReturnCallback(function (EntityImportEvent $event): EntityImportEvent {
                $event->setStatus(EntityImportEvent::NEW, ['campaign' => ['ids' => [1], 'names' => ['Test'], 'count' => 1]]);

                return $event;
            });

        $result = $this->installer->install(self::PACKAGE, 1);

        $this->assertTrue($result['success']);
        $this->assertFileExists($this->tmpRoot.'/media/files/images/logo.png');
    }

    public function testUninstallIsNoOpWhenPackageWasNeverInstalled(): void
    {
        $this->dispatcher->expects($this->never())->method('dispatch');

        $this->installer->uninstall(self::PACKAGE);
    }

    public function testUninstallDispatchesUndoEventsAndRemovesPackageFromState(): void
    {
        $summary = [
            [
                EntityImportEvent::NEW => [
                    'campaign' => ['ids' => [1], 'names' => ['Test'], 'count' => 1],
                ],
            ],
        ];
        $this->writeInstalledState([self::PACKAGE => $summary]);

        $this->dispatcher->expects($this->once())
            ->method('dispatch')
            ->with($this->isInstanceOf(EntityImportUndoEvent::class));

        $this->installer->uninstall(self::PACKAGE);

        $this->assertFalse($this->installer->isInstalled(self::PACKAGE));
    }

    /**
     * Entities recorded under the `update` status matched an existing UUID and pre-date the
     * install, so uninstalling the package must never delete them — only entities the install
     * actually created (`new`) belong to the package.
     */
    public function testUninstallNeverDeletesEntitiesThatWereOnlyUpdated(): void
    {
        $summary = [
            [
                EntityImportEvent::NEW    => [
                    'campaign' => ['ids' => [1], 'names' => ['New campaign'], 'count' => 1],
                ],
                EntityImportEvent::UPDATE => [
                    'campaign' => ['ids' => [42], 'names' => ['Pre-existing campaign'], 'count' => 1],
                ],
            ],
        ];
        $this->writeInstalledState([self::PACKAGE => $summary]);

        $this->dispatcher->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(function (EntityImportUndoEvent $event): bool {
                $this->assertSame('campaign', $event->getEntityName());
                $this->assertSame([1], $event->getSummary()['ids'] ?? null);

                return true;
            }));

        $this->installer->uninstall(self::PACKAGE);

        $this->assertFalse($this->installer->isInstalled(self::PACKAGE));
    }

    /**
     * @param array<string, string> $files
     */
    private function buildZip(array $files): string
    {
        $path = $this->tmpRoot.'/fixture_'.bin2hex(random_bytes(4)).'.zip';
        $zip  = new \ZipArchive();
        $this->assertTrue($zip->open($path, \ZipArchive::CREATE));

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
