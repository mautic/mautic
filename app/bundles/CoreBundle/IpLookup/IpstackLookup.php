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

class IpstackLookup extends AbstractRemoteDataLookup
{
    /**
     * @return string
     */
    public function getAttribution()
    {
        return '<a href="https://ipstack.com/" target="_blank">ipstack.com</a> is a free lookup service that leverages GeoLite2 data created by MaxMind.';
    }

    /**
     * @return string
     */
    protected function getUrl()
    {
        if (empty($this->auth)) {
            $this->logger->warning('FreeGeoIP has become IPStack and now requires an API key.');
        }

        return 'http://api.ipstack.com/'.$this->ip.'?access_key='.$this->auth.'&output=json&legacy=1';
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
