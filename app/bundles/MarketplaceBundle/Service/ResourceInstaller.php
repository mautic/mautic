<?php

declare(strict_types=1);

namespace Mautic\MarketplaceBundle\Service;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CoreBundle\Event\EntityImportEvent;
use Mautic\CoreBundle\Event\EntityImportUndoEvent;
use Mautic\CoreBundle\Helper\PathsHelper;
use Mautic\MarketplaceBundle\Api\Connection;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Filesystem\Filesystem;

class ResourceInstaller
{
    private const INSTALLED_FILE = 'marketplace_installed_resources.json';

    public function __construct(
        private Connection $connection,
        private ClientInterface $httpClient,
        private PathsHelper $pathsHelper,
        private EventDispatcherInterface $dispatcher,
        private LoggerInterface $logger,
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
            $zipPath = $this->downloadZip($distUrl, $packageName);
        } catch (\Exception $e) {
            return ['success' => false, 'summary' => [], 'errors' => ['Failed to download package: '.$e->getMessage()]];
        }

        try {
            $fileData = $this->readCampaignJsonFromZip($zipPath);
            $this->prepareImportData($fileData);

            foreach ($fileData as $entity) {
                $event = new EntityImportEvent(Campaign::ENTITY_NAME, $entity, $userId);
                $this->dispatcher->dispatch($event);
                $status = $event->getStatus();
                if (!empty($status)) {
                    $summary[] = $status;
                }
            }
        } catch (\RuntimeException $e) {
            $errors[] = 'Import failed: '.$e->getMessage();
            $this->logger->error('Resource import failed for '.$packageName.': '.$e->getMessage());
        } finally {
            $this->removeFile($zipPath);
        }

        if (empty($errors)) {
            $this->markAsInstalled($packageName, $summary);
        }

        return [
            'success' => empty($errors),
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

    private function downloadZip(string $url, string $packageName): string
    {
        $importDir = $this->pathsHelper->getImportCampaignsPath();
        (new Filesystem())->mkdir($importDir, 0755);

        $fileName = str_replace('/', '_', $packageName).'_'.bin2hex(random_bytes(4)).'.zip';
        $filePath = $importDir.'/'.$fileName;

        $this->logger->debug('Downloading resource package from: '.$url);

        try {
            $response = $this->httpClient->request('GET', $url, [
                'sink'            => $filePath,
                'allow_redirects' => true,
                'headers'         => [
                    'Accept'     => 'application/vnd.github+json',
                    'User-Agent' => 'Mautic Marketplace',
                ],
            ]);
        } catch (GuzzleException $e) {
            throw new \RuntimeException('Download failed: '.$e->getMessage());
        }

        if ($response->getStatusCode() >= 300) {
            $this->removeFile($filePath);
            throw new \RuntimeException('Download returned HTTP '.$response->getStatusCode());
        }

        if (!file_exists($filePath) || 0 === filesize($filePath)) {
            $this->removeFile($filePath);
            throw new \RuntimeException('Downloaded file is empty or missing');
        }

        return $filePath;
    }

    /**
     * Reads campaign.json from the ZIP, skipping composer.json.
     * Returns the data wrapped in an array matching the format expected by EntityImportEvent.
     *
     * @return array<int, array<string, mixed>>
     */
    private function readCampaignJsonFromZip(string $zipPath): array
    {
        $zip = new \ZipArchive();

        if (true !== $zip->open($zipPath)) {
            throw new \RuntimeException('Unable to open ZIP file');
        }

        $jsonContent = null;

        // Look for campaign.json specifically, skip composer.json
        for ($i = 0; $i < $zip->numFiles; ++$i) {
            $name     = $zip->getNameIndex($i);
            $basename = basename($name);

            if ('campaign.json' === $basename) {
                $jsonContent = $zip->getFromIndex($i);
                break;
            }
        }

        // Fallback: find any JSON that is not composer.json
        if (null === $jsonContent) {
            for ($i = 0; $i < $zip->numFiles; ++$i) {
                $name     = $zip->getNameIndex($i);
                $basename = basename($name);

                if ('json' === pathinfo($basename, PATHINFO_EXTENSION) && 'composer.json' !== $basename) {
                    $jsonContent = $zip->getFromIndex($i);
                    break;
                }
            }
        }

        $zip->close();

        if (null === $jsonContent || false === $jsonContent) {
            throw new \RuntimeException('No campaign JSON file found in the package ZIP');
        }

        $data = json_decode($jsonContent, true);

        if (JSON_ERROR_NONE !== json_last_error()) {
            throw new \RuntimeException('Invalid JSON in package: '.json_last_error_msg());
        }

        // Wrap in array if not already (ImportController expects array of entity groups)
        if (!isset($data[0])) {
            $data = [$data];
        }

        return $data;
    }

    /**
     * Prepares import data: adds UUIDs, defaults, and normalizes dependencies
     * to match the format expected by CampaignImportExportSubscriber.
     *
     * @param array<int, array<string, mixed>> &$fileData
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
        $emails    = $group['email'] ?? [];
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
            if (!empty($eventDeps)) {
                $deps['campaign_event'] = $eventDeps;
            }

            // Map lists to campaign
            foreach ($lists as $list) {
                $deps['lists'][] = ['campaign' => $campaignId, 'lists' => $list['id']];
            }
        }

        return empty($deps) ? [] : [$deps];
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

        // Dispatch undo events to delete imported entities
        // Summary structure: [{status_type: {entity_name: {names: [], ids: [], count: N}}}]
        foreach ($importSummary as $statusGroup) {
            if (!is_array($statusGroup)) {
                continue;
            }
            foreach ($statusGroup as $statusType => $entities) {
                if (!is_array($entities)) {
                    continue;
                }
                foreach ($entities as $entityName => $entityInfo) {
                    if (!is_array($entityInfo) || empty($entityInfo['ids'])) {
                        continue;
                    }
                    $undoEvent = new EntityImportUndoEvent($entityName, $entityInfo);
                    $this->dispatcher->dispatch($undoEvent);
                }
            }
        }

        unset($installed[$packageName]);

        $path = $this->getInstalledFilePath();
        file_put_contents($path, json_encode($installed));

        $this->logger->info('Resource package uninstalled: '.$packageName);
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
            (new Filesystem())->mkdir($dir, 0755);
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
