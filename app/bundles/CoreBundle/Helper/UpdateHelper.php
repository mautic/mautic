<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Helper;

use Joomla\Http\HttpFactory;
use Mautic\CoreBundle\Factory\MauticFactory;

/**
 * Helper class for fetching update data
 */
class UpdateHelper
{

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
    }

    /**
     * Fetches a download package from the remote server
     *
     * @param string $kernelRoot
     * @param string $package
     *
     * @return array
     */
    public function fetchPackage($kernelRoot, $package)
    {
        $target = '';

        // Get our HTTP client
        $connector = HttpFactory::getHttp();

        // GET the update data
        try {
            $data = $connector->get($package);
        } catch (\Exception $exception) {
            return array(
                'error'   => true,
                'message' => 'mautic.core.updater.error.fetching.package'
            );
        }

        if ($data->code != 200) {
            return array(
                'error'   => true,
                'message' => 'mautic.core.updater.error.fetching.package'
            );
        }

        // Set the filesystem target
        $target = $kernelRoot . '/cache/' . basename($package);

        // Write the response to the filesystem
        file_put_contents($target, $data->body);

        // Return an array for the sake of consistency
        return array(
            'error' => false
        );
    }

    /**
     * Retrieves the update data from our home server
     *
     * @param string $kernelRoot
     * @param bool   $overrideCache
     *
     * @return array
     */
    public function fetchData($kernelRoot, $overrideCache = false)
    {
        $cacheFile = $kernelRoot . '/cache/lastUpdateCheck.txt';

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

        // Get our HTTP client
        $connector = HttpFactory::getHttp();

        // Before processing the update data, send up our metrics data
        try {
            // Generate a unique instance ID for the site
            $instanceId = hash('sha1', $this->factory->getParameter('secret') . 'Mautic' . $this->factory->getParameter('db_driver'));

            $data = array(
                'application' => 'Mautic',
                'version'     => $this->factory->getVersion(),
                'phpVerison'  => PHP_VERSION,
                'dbDriver'    => $this->factory->getParameter('db_driver'),
                'serverOs'    => php_uname('s') . ' ' . php_uname('r'),
                'instanceId'  => $instanceId
            );

            $connector->post('http://mautic.org/stats/send', $data);
        } catch (\Exception $exception) {
            // Not so concerned about failures here, move along
        }

        // GET the update data
        try {
            $data    = $connector->get('http://mautic.org/downloads/update.json');
            $updates = json_decode($data->body);
        } catch (\Exception $exception) {
            return array(
                'error'   => true,
                'message' => 'mautic.core.updater.error.fetching.updates'
            );
        }

        if ($data->code != 200) {
            return array(
                'error'   => true,
                'message' => 'mautic.core.updater.error.fetching.updates'
            );
        }

        // Check which update stream the usser wants to see data for
        $stability = $this->factory->getParameter('update_stability');
        $latestVersion = $updates->$stability;

        // If the user's up-to-date, go no further
        if (version_compare($this->factory->getVersion(), $latestVersion->version, '>=')) {
            return array(
                'error'   => false,
                'message' => 'mautic.core.updater.running.latest.version'
            );
        }

        // If the user's server doesn't meet the minimum requirements for the update, notify them of such
        if (version_compare($this->factory->getVersion(), $latestVersion->min_version, '<') || version_compare(PHP_VERSION, $latestVersion->php_version, '<')) {
            return array(
                'error'       => false,
                'message'     => 'mautic.core.updater.requirements.not.met',
                'min_version' => $latestVersion->min_version,
                'php_version' => $latestVersion->php_version
            );
        }

        // If we got this far, the user is able to update to the latest version, cache the data first
        $data = array(
            'error'        => false,
            'message'      => 'mautic.core.updater.update.available',
            'version'      => $latestVersion->version,
            'announcement' => $latestVersion->announcement,
            'package'      => $latestVersion->package,
            'checkedTime'  => time(),
            'stability'    => $this->factory->getParameter('update_stability')
        );

        file_put_contents($cacheFile, json_encode($data));

        return $data;
    }
}
