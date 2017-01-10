<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Helper;

use Joomla\Http\HttpFactory;
use Mautic\CoreBundle\Factory\MauticFactory;

/**
 * Helper class for fetching update data.
 */
class UpdateHelper
{
    /**
     * @var \Joomla\Http\Http
     */
    private $connector;

    /**
     * @var MauticFactory
     */
    private $factory;

    /**
     * @param MauticFactory $factory
     */
    public function __construct(MauticFactory $factory)
    {
        $this->factory = $factory;

        $this->connector = HttpFactory::getHttp();
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
            $data = $this->connector->get($package);
        } catch (\Exception $exception) {
            $logger = $this->factory->getLogger();
            $logger->addError('An error occurred while attempting to fetch the package: '.$exception->getMessage());

            return [
                'error'   => true,
                'message' => 'mautic.core.updater.error.fetching.package',
            ];
        }

        if ($data->code != 200) {
            return [
                'error'   => true,
                'message' => 'mautic.core.updater.error.fetching.package',
            ];
        }

        // Set the filesystem target
        $target = $this->factory->getSystemPath('cache').'/'.basename($package);

        // Write the response to the filesystem
        file_put_contents($target, $data->body);

        // Return an array for the sake of consistency
        return [
            'error' => false,
        ];
    }

    /**
     * Tries to get server OS.
     *
     * @return string
     */
    public function getServerOs()
    {
        if (function_exists('php_uname')) {
            return php_uname('s').' '.php_uname('r');
        } elseif (defined('PHP_OS')) {
            return PHP_OS;
        }

        return 'N/A';
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
        $cacheFile = $this->factory->getSystemPath('cache').'/lastUpdateCheck.txt';

        // Check if we have a cache file and try to return cached data if so
        if (!$overrideCache && is_readable($cacheFile)) {
            $update = (array) json_decode(file_get_contents($cacheFile));

            // Check if the user has changed the update channel, if so the cache is invalidated
            if ($update['stability'] == $this->factory->getParameter('update_stability')) {
                // If we're within the cache time, return the cached data
                if ($update['checkedTime'] > strtotime('-3 hours')) {
                    return $update;
                }
            }
        }

        // Before processing the update data, send up our metrics
        try {
            // Generate a unique instance ID for the site
            $instanceId = hash('sha1', $this->factory->getParameter('secret_key').'Mautic'.$this->factory->getParameter('db_driver'));

            $data = array_map(
                'trim',
                [
                    'application'   => 'Mautic',
                    'version'       => $this->factory->getVersion(),
                    'phpVersion'    => PHP_VERSION,
                    'dbDriver'      => $this->factory->getParameter('db_driver'),
                    'serverOs'      => $this->getServerOs(),
                    'instanceId'    => $instanceId,
                    'installSource' => $this->factory->getParameter('install_source', 'Mautic'),
                ]
            );

            $this->connector->post('https://updates.mautic.org/stats/send', $data, [], 10);
        } catch (\Exception $exception) {
            // Not so concerned about failures here, move along
        }

        // Get the update data
        try {
            $appData = array_map(
                'trim',
                [
                    'appVersion' => $this->factory->getVersion(),
                    'phpVersion' => PHP_VERSION,
                    'stability'  => $this->factory->getParameter('update_stability'),
                ]
            );

            $data   = $this->connector->post('https://updates.mautic.org/index.php?option=com_mauticdownload&task=checkUpdates', $appData, [], 10);
            $update = json_decode($data->body);
        } catch (\Exception $exception) {
            // Log the error
            $logger = $this->factory->getLogger();
            $logger->addError('An error occurred while attempting to fetch updates: '.$exception->getMessage());

            return [
                'error'   => true,
                'message' => 'mautic.core.updater.error.fetching.updates',
            ];
        }

        if ($data->code != 200) {
            // Log the error
            $logger = $this->factory->getLogger();
            $logger->addError(
                sprintf(
                    'An unexpected %1$s code was returned while attempting to fetch updates.  The message received was: %2$s',
                    $data->code,
                    is_string($data->body) ? $data->body : implode('; ', $data->body)
                )
            );

            return [
                'error'   => true,
                'message' => 'mautic.core.updater.error.fetching.updates',
            ];
        }

        // If the user's up-to-date, go no further
        if ($update->latest_version) {
            return [
                'error'   => false,
                'message' => 'mautic.core.updater.running.latest.version',
            ];
        }

        // Last sanity check, if the $update->version is older than our current version
        if (version_compare($this->factory->getVersion(), $update->version, 'ge')) {
            return [
                'error'   => false,
                'message' => 'mautic.core.updater.running.latest.version',
            ];
        }

        // The user is able to update to the latest version, cache the data first
        $data = [
            'error'        => false,
            'message'      => 'mautic.core.updater.update.available',
            'version'      => $update->version,
            'announcement' => $update->announcement,
            'package'      => $update->package,
            'checkedTime'  => time(),
            'stability'    => $this->factory->getParameter('update_stability'),
        ];

        file_put_contents($cacheFile, json_encode($data));

        return $data;
    }
}
