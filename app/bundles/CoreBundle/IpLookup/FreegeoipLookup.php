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

class FreegeoipLookup extends AbstractRemoteDataLookup
{
    /**
     * @return string
     */
    public function getAttribution()
    {
        return '<a href="https://freegeoip.net/" target="_blank">freegeoip.net</a> is a free lookup service that leverages GeoLite2 data created by MaxMind.';
    }

    /**
     * @return string
     */
    protected function getUrl()
    {
        return "http://freegeoip.net/json/{$this->ip}";
    }

    /**
     * @param $response
     */
    protected function parseResponse($response)
    {
        $data = json_decode($response);

        if ($data) {
            foreach ($data as $key => $value) {
                switch ($key) {
                    case 'region_name':
                        $key = 'region';
                        break;
                    case 'country_name':
                        $key = 'country';
                        break;
                    case 'zip_code':
                        $key = 'zipcode';
                        break;
                    case 'time_zone':
                        $key = 'timezone';
                        break;
                }

                $this->$key = $value;
            }
        }
    }
}
