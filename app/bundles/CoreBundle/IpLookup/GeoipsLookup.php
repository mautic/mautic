<?php

/*
 * @copyright   2015 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\IpLookup;

class GeoipsLookup extends AbstractRemoteDataLookup
{
    /**
     * @return string
     */
    public function getAttribution()
    {
        return '<a href="http://www.geoips.com/" target="_blank">GeoIPs</a> offers tiered subscriptions for lookups.';
    }

    /**
     * @return string
     */
    protected function getUrl()
    {
        return "http://api.geoips.com/ip/{$this->ip}/key/{$this->auth}/output/json";
    }

    /**
     * @param $response
     */
    protected function parseResponse($response)
    {
        $data = json_decode($response);

        if ($data && !empty($data->response->location)) {
            foreach ($data->response->location as $key => $value) {
                switch ($key) {
                    case 'city_name':
                        $key = 'city';
                        break;
                    case 'region_name':
                        $key = 'region';
                        break;
                    case 'country_name':
                        $key = 'country';
                        break;
                    case 'owner':
                        $key = 'isp';
                        break;
                }

                $this->$key = $value;
            }
        }
    }
}
