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
     * Retrieves the update data from our home server
     *
     * @return array
     */
    public function fetchData()
    {
        // Get our HTTP client
        $connector = HttpFactory::getHttp();

        // GET the update data
        // TODO - Change to the real URL at some point
        try {
            $data    = $connector->get('http://mautic/update.json');
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
        // TODO - When the param exists, use it instead of hardcoding to stable
        $stability = 'stable';
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

        // If we got this far, the user is able to update to the latest version
        return array(
            'error'        => false,
            'message'      => 'mautic.core.updater.update.available',
            'version'      => $latestVersion->version,
            'announcement' => $latestVersion->announcement
        );
    }
}
