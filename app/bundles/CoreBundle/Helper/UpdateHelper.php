<?php

namespace Mautic\CoreBundle\Helper;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Mautic\CoreBundle\Helper\Update\Exception\CouldNotFetchLatestVersionException;
use Mautic\CoreBundle\Helper\Update\Exception\LatestVersionSupportedException;
use Mautic\CoreBundle\Helper\Update\Exception\UpdateCacheDataNeedsToBeRefreshedException;
use Mautic\CoreBundle\Helper\Update\Github\Release;
use Mautic\CoreBundle\Helper\Update\Github\ReleaseParser;
use Mautic\CoreBundle\Helper\Update\PreUpdateChecks\PreUpdateCheckError;
use Mautic\CoreBundle\Helper\Update\PreUpdateChecks\PreUpdateCheckResult;
use Mautic\CoreBundle\Release\Metadata;
use Monolog\Logger;

/**
 * Helper class for fetching update data.
 */
class UpdateHelper
{
    /**
     * @var PathsHelper
     */
    private $pathsHelper;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var CoreParametersHelper
     */
    private $coreParametersHelper;

    /**
     * @var Client
     */
    private $client;

    /**
     * @var ReleaseParser
     */
    private $releaseParser;

    /**
     * @var string
     */
    private $phpVersion;

    /**
     * @var string
     */
    private $mauticVersion;

    private PreUpdateCheckHelper $preUpdateCheckHelper;

    public function __construct(
        PathsHelper $pathsHelper,
        Logger $logger,
        CoreParametersHelper $coreParametersHelper,
        Client $client,
        ReleaseParser $releaseParser,
        PreUpdateCheckHelper $preUpdateCheckHelper
    ) {
        $this->pathsHelper          = $pathsHelper;
        $this->logger               = $logger;
        $this->coreParametersHelper = $coreParametersHelper;
        $this->client               = $client;
        $this->releaseParser        = $releaseParser;
        $this->preUpdateCheckHelper = $preUpdateCheckHelper;

        $this->mauticVersion = defined('MAUTIC_VERSION') ? MAUTIC_VERSION : 'unknown';
        $this->phpVersion    = defined('PHP_VERSION') ? PHP_VERSION : 'unknown';
    }

    /**
     * Fetches a download package from the remote server.
     *
     * @param string $package
     *
     * @return array
     */
    public function fetchPackage($package)
    {
        // GET the update data
        try {
            $response = $this->client->request('GET', $package);
            if (200 !== $response->getStatusCode()) {
                throw new \Exception('error code '.$response->getStatusCode());
            }

            $data = $response->getBody()->getContents();
        } catch (\Exception $exception) {
            $this->logger->addError('An error occurred while attempting to fetch the package: '.$exception->getMessage());

            return [
                'error'   => true,
                'message' => 'mautic.core.updater.error.fetching.package',
            ];
        }

        // Set the filesystem target
        $target = $this->pathsHelper->getSystemPath('cache').'/'.basename($package);

        // Write the response to the filesystem
        file_put_contents($target, $data);

        // Return an array for the sake of consistency
        return [
            'error' => false,
        ];
    }

    /**
     * Retrieves the update data from our home server.
     *
     * @param bool $overrideCache
     *
     * @return array
     */
    public function fetchData($overrideCache = false)
    {
        $cacheFile       = $this->pathsHelper->getSystemPath('cache').'/lastUpdateCheck.txt';
        $updateStability = $this->coreParametersHelper->get('update_stability');

        try {
            if (!$overrideCache && is_readable($cacheFile)) {
                return $this->checkCachedUpdateData($cacheFile, $updateStability);
            }
        } catch (UpdateCacheDataNeedsToBeRefreshedException $exception) {
            // Fetch a fresh list
        }

        // Send statistics if enabled
        $this->sendStats();

        // Fetch the latest version
        try {
            $release = $this->fetchLatestCompatibleVersion($updateStability);
        } catch (LatestVersionSupportedException $exception) {
            return [
                'error'   => false,
                'message' => 'mautic.core.updater.running.latest.version',
            ];
        } catch (CouldNotFetchLatestVersionException $exception) {
            return [
                'error'   => true,
                'message' => 'mautic.core.updater.error.fetching.updates',
            ];
        } catch (RequestException $exception) {
            if (!empty($exception->getResponse())) {
                $this->logger->error(
                    sprintf(
                        'UPDATE CHECK: Could not fetch a release list: %s (%s)',
                        $exception->getResponse()->getStatusCode(),
                        $exception->getResponse()->getReasonPhrase()
                    )
                );
            } else {
                $this->logger->error(
                    sprintf(
                        'UPDATE CHECK: Could not fetch a release list: %s',
                        $exception->getMessage()
                    )
                );
            }

            return [
                'error'   => true,
                'message' => 'mautic.core.updater.error.fetching.updates',
            ];
        } catch (\Exception $exception) {
            $this->logger->error(sprintf('UPDATE CHECK: %s', $exception->getMessage()));

            return [
                'error'   => true,
                'message' => 'mautic.core.updater.error.fetching.updates',
            ];
        }

        // The user is able to update to the latest version, cache the data first
        $data = [
            'error'        => false,
            'message'      => 'mautic.core.updater.update.available',
            'version'      => $release->getVersion(),
            'announcement' => $release->getAnnouncementUrl(),
            'package'      => $release->getDownloadUrl(),
            'stability'    => $release->getStability(),
            'checkedTime'  => time(),
            'metadata'     => $release->getMetadata(),
        ];

        file_put_contents($cacheFile, json_encode($data));

        return $data;
    }

    /**
     * Runs all pre-update checks. This returns an array of PreUpdateCheckResult objects,
     * which you can loop through to see if all checks passed.
     *
     * @return PreUpdateCheckResult[]
     */
    public function runPreUpdateChecks(): array
    {
        $checkResults = [];
        $checks       = $this->preUpdateCheckHelper->getChecks();
        $updateData   = $this->fetchData();

        if (true === $updateData['error']) {
            $checkResults[] = new PreUpdateCheckResult(false, null, [new PreUpdateCheckError($updateData['message'])]);
        } elseif (false === $updateData['error'] && 'mautic.core.updater.running.latest.version' === $updateData['message']) {
            // If we're already running the latest version, let's consider that an error so that the updater doesn't accidentally continue.
            $checkResults[] = new PreUpdateCheckResult(false, null, [new PreUpdateCheckError('mautic.core.updater.running.latest.version')]);
        } elseif (empty($updateData['metadata'])) {
            $checkResults[] = new PreUpdateCheckResult(false, null, [new PreUpdateCheckError('mautic.core.update.check.error.release_data')]);
        }

        if (!empty($checkResults)) {
            return $checkResults;
        }

        foreach ($checks as $check) {
            $check->setUpdateCandidateMetadata($updateData['metadata']);

            try {
                $checkResults[] = $check->runCheck();
            } catch (\Exception $e) {
                // Checks are supposed to catch errors themselves and return them in their PreUpdateCheckResult, but we catch here just in case.
                $checkResults[] = new PreUpdateCheckResult(false, $check, [new PreUpdateCheckError('Unknown error while running '.get_class($check).': '.$e->getMessage())]);
            }
        }

        return $checkResults;
    }

    private function sendStats()
    {
        if (!$statUrl = $this->coreParametersHelper->get('stats_update_url')) {
            // Stat collection disabled
            return;
        }

        // Before processing the update data, send up our metrics
        try {
            $key           = $this->coreParametersHelper->get('secret_key');
            $dbDriver      = $this->coreParametersHelper->get('db_driver');
            $installSource = $this->coreParametersHelper->get('install_source', 'Mautic');

            // Generate a unique instance ID for the site
            $instanceId = hash('sha1', $key.$installSource.$dbDriver);

            $data = array_map(
                'trim',
                [
                    'application'   => 'Mautic',
                    'version'       => $this->mauticVersion,
                    'phpVersion'    => $this->phpVersion,
                    'dbDriver'      => $dbDriver,
                    'serverOs'      => $this->getServerOs(),
                    'instanceId'    => $instanceId,
                    'installSource' => $installSource,
                ]
            );

            $options = [
                \GuzzleHttp\RequestOptions::FORM_PARAMS     => $data,
                \GuzzleHttp\RequestOptions::CONNECT_TIMEOUT => 10,
                \GuzzleHttp\RequestOptions::HEADERS         => [
                    'Accept' => '*/*',
                ],
            ];

            $this->client->request('POST', $statUrl, $options);
        } catch (RequestException $exception) {
            if (!empty($exception->getResponse())) {
                $this->logger->error(
                    sprintf(
                        'STAT UPDATE: Error communicating with the stat server: %s (%s)',
                        $exception->getResponse()->getStatusCode(),
                        $exception->getResponse()->getReasonPhrase()
                    )
                );
            } else {
                $this->logger->error(
                    sprintf(
                        'STAT UPDATE: Error communicating with the stat server: %s',
                        $exception->getMessage()
                    )
                );
            }
        } catch (\Exception $exception) {
            // Not so concerned about failures here, move along
            $this->logger->error(sprintf('STAT UPDATE: %s', $exception->getMessage()));
        }
    }

    /**
     * @throws UpdateCacheDataNeedsToBeRefreshedException
     */
    private function checkCachedUpdateData(string $cacheFile, string $updateStability): array
    {
        // Check if we have a cache file and try to return cached data if so
        $update = json_decode(file_get_contents($cacheFile), true);

        if (!empty($update['metadata'])) {
            $update['metadata'] = new Metadata($update['metadata']);
        }

        // Check if the user has changed the update channel, if so the cache is invalidated
        $expiredAt = strtotime('-3 hours');
        if ($update['stability'] !== $updateStability || $update['checkedTime'] <= $expiredAt) {
            throw new UpdateCacheDataNeedsToBeRefreshedException();
        }

        return $update;
    }

    /**
     * @throws CouldNotFetchLatestVersionException
     * @throws LatestVersionSupportedException
     */
    private function fetchLatestCompatibleVersion(string $updateStability): Release
    {
        // Check if the in-app updater is enabled
        if (!$updateUrl = $this->coreParametersHelper->get('system_update_url')) {
            // In app updating is disabled
            throw new LatestVersionSupportedException();
        }

        // Fetch a new list of data
        $response = $this->client->request('GET', $updateUrl);
        if (200 !== $response->getStatusCode()) {
            // Log the error
            $this->logger->error(
                sprintf(
                    'UPDATE CHECK: Failed fetching releases: %s (%s)',
                    $response->getStatusCode(),
                    $response->getReasonPhrase()
                )
            );

            throw new CouldNotFetchLatestVersionException();
        }

        $releases = json_decode($response->getBody()->getContents(), true);
        if (empty($releases)) {
            $this->logger->error(sprintf('UPDATE CHECK FAILED: response body for %s is not json', $updateUrl));

            throw new CouldNotFetchLatestVersionException();
        }

        return $this->releaseParser->getLatestSupportedRelease($releases, $this->mauticVersion, $updateStability);
    }

    /**
     * Tries to get server OS.
     *
     * @return string
     */
    private function getServerOs()
    {
        if (function_exists('php_uname')) {
            return php_uname('s').' '.php_uname('r');
        } elseif (defined('PHP_OS')) {
            return PHP_OS;
        }

        return 'unknown';
    }
}
