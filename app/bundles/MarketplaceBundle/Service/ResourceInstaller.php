<?php

declare(strict_types=1);

namespace Mautic\MarketplaceBundle\Service;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CoreBundle\Event\EntityImportEvent;
use Mautic\CoreBundle\Event\EntityImportUndoEvent;
use Mautic\CoreBundle\Helper\ImportHelper;
use Mautic\CoreBundle\Helper\PathsHelper;
use Mautic\MarketplaceBundle\Api\Connection;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Filesystem\Filesystem;

final readonly class ResourceInstaller implements ResourceInstallerInterface
{
    private const string INSTALLED_FILE = 'marketplace_installed_resources.json';

    private const int MAX_REDIRECTS = 5;

    // A resource is a campaign export (JSON + a handful of images), so a legitimate ZIP is
    // small; this caps how much a malicious/misconfigured dist URL can make us write to disk.
    private const int MAX_DOWNLOAD_BYTES = 52428800; // 50 MB

    public function __construct(
        private Connection $connection,
        #[Autowire(service: 'mautic.http.client')]
        private ClientInterface $httpClient,
        private PathsHelper $pathsHelper,
        private EventDispatcherInterface $dispatcher,
        private LoggerInterface $logger,
        private ImportHelper $importHelper,
        #[Autowire(param: 'kernel.debug')]
        private bool $debug = false,
    ) {
    }

    public function isInstalled(string $packageName): bool
    {
        $installed = $this->getInstalledData();

        return isset($installed[$packageName]);
    }

    /**
     * @return array{success: bool, summary: array<mixed>, errors: array<string>}
     */
    public function install(string $packageName, int $userId): array
    {
        $errors  = [];
        $summary = [];

        try {
            $distUrl = $this->getDistUrl($packageName);
        } catch (\Exception $e) {
            return ['success' => false, 'summary' => [], 'errors' => [$e->getMessage()]];
        }

        try {
            $zipPath = $this->downloadZip($distUrl);
        } catch (\Exception $e) {
            return ['success' => false, 'summary' => [], 'errors' => ['Failed to download package: '.$e->getMessage()]];
        }

        try {
            // Extract through the shared import path so packaged assets (e.g. email
            // builder images) are restored to the media dir before the import events run.
            $fileData = $this->importHelper->readZipFile($zipPath);

            // Wrap in array if not already (EntityImportEvent expects a list of entity groups)
            if (!isset($fileData[0])) {
                $fileData = [$fileData];
            }

            $this->prepareImportData($fileData);

            foreach ($fileData as $entity) {
                $event = new EntityImportEvent(Campaign::ENTITY_NAME, $entity, $userId);
                $this->dispatcher->dispatch($event);
                $status = $event->getStatus();

                $statusErrors = $status[EntityImportEvent::ERRORS] ?? [];
                if (!empty($statusErrors)) {
                    $errors[] = 'Import reported errors: '.json_encode($statusErrors);
                    // Roll back whatever this partial import already created so a failed
                    // install never leaves orphaned entities behind.
                    $this->undoNewEntities($summary);
                    $summary = [];
                    break;
                }

                if ([] !== $status) {
                    $summary[] = $status;
                }
            }
        } catch (\RuntimeException $e) {
            $errors[] = 'Import failed: '.$e->getMessage();
            $this->logger->error('Resource import failed for '.$packageName.': '.$e->getMessage());
        } finally {
            $this->removeFile($zipPath);
        }

        if ([] === $errors) {
            $this->markAsInstalled($packageName, $summary);
        }

        return [
            'success' => [] === $errors,
            'summary' => $summary,
            'errors'  => $errors,
        ];
    }

    private function getDistUrl(string $packageName): string
    {
        $payload = $this->connection->getPackage($packageName);
        $package = $payload['package'] ?? $payload;

        $versions = $package['versions'] ?? [];

        // Find latest stable version, fallback to first available
        $version = null;
        foreach ($versions as $v) {
            if (!is_array($v) || !isset($v['dist']['url'])) {
                continue;
            }
            // Prefer stable versions (not dev-)
            if (!str_starts_with($v['version'] ?? '', 'dev-')) {
                $version = $v;
                break;
            }
            if (null === $version) {
                $version = $v;
            }
        }

        if (null === $version || empty($version['dist']['url'])) {
            throw new \RuntimeException('No downloadable version found for '.$packageName);
        }

        return $version['dist']['url'];
    }

    private function downloadZip(string $url): string
    {
        // The dist URL comes from the package's own metadata, so anyone who can publish to the
        // marketplace picks it. Vet it before we connect, and again on every redirect.
        $this->assertSafeDownloadUrl($url);

        $importDir = $this->pathsHelper->getImportCampaignsPath();
        new Filesystem()->mkdir($importDir, 0755);

        // Use a fully random file name so the path never derives from user-controlled
        // input (the package name), preventing path-injection.
        $fileName = 'marketplace_resource_'.bin2hex(random_bytes(16)).'.zip';
        $filePath = $importDir.'/'.$fileName;

        $this->logger->debug('Downloading resource package from: '.$url);

        try {
            $response = $this->httpClient->request('GET', $url, [
                'sink'            => $filePath,
                'allow_redirects' => [
                    'max'         => self::MAX_REDIRECTS,
                    'protocols'   => $this->debug ? ['http', 'https'] : ['https'],
                    'strict'      => true,
                    'referer'     => false,
                    'on_redirect' => function (RequestInterface $request, ResponseInterface $response, UriInterface $uri): void {
                        $this->assertSafeDownloadUrl((string) $uri);
                    },
                ],
                'headers' => [
                    'User-Agent' => 'Mautic Marketplace',
                ],
                // Reject an honestly-declared oversized download before any body is read.
                'on_headers' => function (ResponseInterface $response): void {
                    $this->assertContentLengthWithinLimit($response);
                },
                // Chunked/absent Content-Length responses are caught mid-stream instead.
                'progress' => function (int $downloadTotal, int $downloadedBytes) use ($filePath): void {
                    if ($downloadedBytes > self::MAX_DOWNLOAD_BYTES) {
                        $this->removeFile($filePath);
                        throw new \RuntimeException('Download exceeds the maximum allowed size of '.self::MAX_DOWNLOAD_BYTES.' bytes.');
                    }
                },
            ]);
        } catch (GuzzleException $e) {
            $this->removeFile($filePath);
            throw new \RuntimeException('Download failed: '.$e->getMessage(), $e->getCode(), $e);
        }

        if ($response->getStatusCode() >= 300) {
            $this->removeFile($filePath);
            throw new \RuntimeException('Download returned HTTP '.$response->getStatusCode());
        }

        // Backstop for the (mocked or otherwise non-conforming) clients that ignore the
        // progress/on_headers hooks above and stream the whole body regardless.
        if (file_exists($filePath) && filesize($filePath) > self::MAX_DOWNLOAD_BYTES) {
            $this->removeFile($filePath);
            throw new \RuntimeException('Download exceeds the maximum allowed size of '.self::MAX_DOWNLOAD_BYTES.' bytes.');
        }

        if (!file_exists($filePath) || 0 === filesize($filePath)) {
            $this->removeFile($filePath);
            throw new \RuntimeException('Downloaded file is empty or missing');
        }

        return $filePath;
    }

    private function assertContentLengthWithinLimit(ResponseInterface $response): void
    {
        $contentLength = $response->getHeaderLine('Content-Length');
        if ('' !== $contentLength && (int) $contentLength > self::MAX_DOWNLOAD_BYTES) {
            throw new \RuntimeException('Download exceeds the maximum allowed size of '.self::MAX_DOWNLOAD_BYTES.' bytes.');
        }
    }

    /**
     * Rejects anything that isn't a plain https URL pointing at a publicly routable host, so a
     * crafted dist URL can't turn an install into a request against the server's own network.
     *
     * A local install talks to a marketplace on http://127.0.0.1, so both rules are relaxed when
     * debug is on — never in production, where debug is off.
     */
    private function assertSafeDownloadUrl(string $url): void
    {
        $parts  = parse_url($url);
        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        $host   = (string) ($parts['host'] ?? '');

        if ('' === $host || !\in_array($scheme, $this->debug ? ['http', 'https'] : ['https'], true)) {
            throw new \RuntimeException(\sprintf('Refusing to download "%s": expected an absolute https URL.', $url));
        }

        foreach ($this->resolveHost($host) as $address) {
            if (filter_var($address, \FILTER_VALIDATE_IP, \FILTER_FLAG_NO_PRIV_RANGE | \FILTER_FLAG_NO_RES_RANGE)) {
                continue;
            }

            if ($this->debug) {
                $this->logger->warning(\sprintf('Marketplace download host "%s" resolves to the non-public address %s; allowed because debug is on.', $host, $address));
                continue;
            }

            throw new \RuntimeException(\sprintf('Refusing to download "%s": the host resolves to a non-public address.', $url));
        }
    }

    /**
     * Every address the host points at. An unresolvable host yields none — there is nothing to
     * connect to, so let the HTTP client report it rather than guessing here.
     *
     * @return list<string>
     */
    private function resolveHost(string $host): array
    {
        // IPv6 literals arrive wrapped in brackets.
        $host = trim($host, '[]');

        if (filter_var($host, \FILTER_VALIDATE_IP)) {
            return [$host];
        }

        $addresses = [];
        foreach (@dns_get_record($host, \DNS_A | \DNS_AAAA) ?: [] as $record) {
            $address = $record['ip'] ?? $record['ipv6'] ?? null;
            if (\is_string($address) && '' !== $address) {
                $addresses[] = $address;
            }
        }

        return $addresses;
    }

    /**
     * Prepares import data: adds UUIDs, defaults, and normalizes dependencies
     * to match the format expected by CampaignImportExportSubscriber.
     *
     * @param array<int, array<string, mixed>> $fileData
     */
    private function prepareImportData(array &$fileData): void
    {
        $entityTypes = ['campaign', 'campaign_event', 'email', 'lists', 'forms', 'form_field', 'page', 'asset'];

        foreach ($fileData as &$group) {
            foreach ($entityTypes as $type) {
                if (!isset($group[$type]) || !is_array($group[$type])) {
                    continue;
                }
                foreach ($group[$type] as &$item) {
                    if (!is_array($item)) {
                        continue;
                    }
                    if (empty($item['uuid'])) {
                        $item['uuid'] = Uuid::uuid4()->toString();
                    }
                    if ('campaign' === $type && !isset($item['canvas_settings'])) {
                        $item['canvas_settings'] = ['nodes' => [], 'connections' => []];
                    }
                }
            }

            // Normalize dependencies to the format expected by the subscriber
            if (!isset($group['dependencies']) || !is_array($group['dependencies'])) {
                $group['dependencies'] = $this->buildDependencies($group);
            } elseif (!isset($group['dependencies'][0])) {
                // Dependencies is an object, convert to expected array format
                $group['dependencies'] = $this->buildDependencies($group);
            }
        }
    }

    /**
     * Builds dependencies array from campaign data in the format expected by CampaignImportExportSubscriber.
     *
     * @param array<string, mixed> $group
     *
     * @return array<int, array<string, array<int, array<string, int>>>>
     */
    private function buildDependencies(array $group): array
    {
        $deps = [];

        $campaigns = $group['campaign'] ?? [];
        $events    = $group['campaign_event'] ?? [];
        $lists     = $group['lists'] ?? [];

        foreach ($campaigns as $campaign) {
            $campaignId = $campaign['id'] ?? null;
            if (null === $campaignId) {
                continue;
            }

            // Map campaign events to their campaign
            $eventDeps = [];
            foreach ($events as $evt) {
                if (($evt['campaign_id'] ?? null) == $campaignId) {
                    $dep = ['campaign' => $campaignId, 'campaign_event' => $evt['id']];
                    if (!empty($evt['channel']) && 'email' === $evt['channel'] && !empty($evt['channel_id'])) {
                        $dep['email'] = $evt['channel_id'];
                    }
                    $eventDeps[] = $dep;
                }
            }
            if ([] !== $eventDeps) {
                $deps['campaign_event'] = $eventDeps;
            }

            // Map lists to campaign
            foreach ($lists as $list) {
                $deps['lists'][] = ['campaign' => $campaignId, 'lists' => $list['id']];
            }
        }

        return [] === $deps ? [] : [$deps];
    }

    /**
     * @return array<string, array<mixed>>
     */
    private function getInstalledData(): array
    {
        $path = $this->getInstalledFilePath();

        if (!file_exists($path)) {
            return [];
        }

        $data = json_decode(file_get_contents($path), true);

        if (!is_array($data)) {
            return [];
        }

        // Migrate old format (simple array of strings) to new format (object)
        if (isset($data[0]) && is_string($data[0])) {
            $migrated = [];
            foreach ($data as $name) {
                $migrated[$name] = [];
            }

            return $migrated;
        }

        return $data;
    }

    public function uninstall(string $packageName): void
    {
        $installed = $this->getInstalledData();

        if (!isset($installed[$packageName])) {
            return;
        }

        $importSummary = $installed[$packageName];

        $this->undoNewEntities($importSummary);

        unset($installed[$packageName]);

        $path = $this->getInstalledFilePath();
        file_put_contents($path, json_encode($installed));

        $this->logger->info('Resource package uninstalled: '.$packageName);
    }

    /**
     * Dispatches undo events for entities that were newly created by the install. Entities
     * that matched an existing UUID and were only updated must never be deleted here, since
     * they pre-date the install and belong to the user, not the package.
     *
     * @param array<int, mixed> $summary
     */
    private function undoNewEntities(array $summary): void
    {
        foreach ($summary as $statusGroup) {
            if (!is_array($statusGroup) || !isset($statusGroup[EntityImportEvent::NEW]) || !is_array($statusGroup[EntityImportEvent::NEW])) {
                continue;
            }

            foreach ($statusGroup[EntityImportEvent::NEW] as $entityName => $entityInfo) {
                if (!is_array($entityInfo) || empty($entityInfo['ids'])) {
                    continue;
                }
                $this->dispatcher->dispatch(new EntityImportUndoEvent($entityName, $entityInfo));
            }
        }
    }

    /**
     * @param array<mixed> $summary
     */
    private function markAsInstalled(string $packageName, array $summary = []): void
    {
        $installed               = $this->getInstalledData();
        $installed[$packageName] = $summary;

        $path = $this->getInstalledFilePath();
        $dir  = dirname($path);

        if (!is_dir($dir)) {
            new Filesystem()->mkdir($dir, 0755);
        }

        file_put_contents($path, json_encode($installed));
    }

    private function getInstalledFilePath(): string
    {
        return $this->pathsHelper->getSystemPath('root').'/var/'.self::INSTALLED_FILE;
    }

    private function removeFile(string $path): void
    {
        if (file_exists($path)) {
            @unlink($path);
        }
    }
}
