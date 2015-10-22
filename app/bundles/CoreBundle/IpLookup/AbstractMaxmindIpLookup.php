<?php
/**
 * @package     Mautic
 * @copyright   2015 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\IpLookup;

/**
 * Class AbstractMaxmindLookup
 */
abstract class AbstractMaxmindIpLookup extends AbstractIpLookup
{
    /**
     * @return string
     */
    protected function getUrl()
    {
        $url = "https://{$this->auth}@geoip.maxmind.com/geoip/v2.1/";

        switch ($this->getName()) {
            case 'maxmind_country':
                $url .= 'country';
                break;
            case 'maxmind_precision':
                $url .= 'city';
                break;
            case 'maxmind_omni':
                $url .= 'insights';
                break;
        }

        $url .= "/{$this->ip}";

        return $url;
    }

    /**
     * @param $response
     */
    public function parseData($response)
    {
        $data = json_decode($response);

        if ($data && empty($data->error)) {
            $this->city      = $data->city->names->en;
            $this->region    = $data->subdivisions->names->en;
            $this->country   = $data->country->names->en;
            $this->latitude  = $data->location->latitude;
            $this->longitude = $data->location->longitude;
            $this->timezone = $data->location->time_zone;

            if (isset($data->traits->isp)) {
                $this->isp = $data->traits->isp;
            }

            if (isset($data->traits->organization)) {
                $this->organization = $data->traits->organization;
            }
        }
    }
}