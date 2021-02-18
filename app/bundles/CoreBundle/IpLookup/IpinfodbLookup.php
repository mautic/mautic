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

class IpinfodbLookup extends AbstractRemoteDataLookup
{
    /**
     * @return string
     */
    public function getAttribution()
    {
        return '<a href="http://www.ipinfodb.com/" target="_blank">iPInfoDB</a> offers a free service (2 lookups per second) that leverages data from IP2Location. API key required.';
    }

    /**
     * @return string
     */
    protected function getUrl()
    {
        return "http://api.ipinfodb.com/v3/ip-city/?key={$this->auth}&format=json&ip={$this->ip}";
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
                    case 'cityName':
                        $key = 'city';
                        break;
                    case 'regionName':
                        $key = 'region';
                        break;
                    case 'countryName':
                        $key = 'country';
                        break;
                    case 'zipCode':
                        $key = 'zipcode';
                        break;
                    case 'timeZone':
                        $key = 'timezone';
                        break;
                }

                $this->$key = ucfirst($value);
            }
        }
    }
}
