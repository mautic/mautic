<?php
/**
 * @package     Mautic
 * @copyright   2015 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\IpLookup;


class IpinfodbIpLookup extends AbstractIpLookup
{
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
    public function parseData($response)
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
                }

                $this->$key = ucfirst($value);
            }
        }
    }
}