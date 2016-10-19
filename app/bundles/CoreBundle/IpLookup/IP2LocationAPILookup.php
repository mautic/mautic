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

class IP2LocationAPILookup extends AbstractRemoteDataLookup
{
    /**
     * @return string
     */
    public function getAttribution()
    {
        return '<a href="http://IP2Location.com/" target="_blank">IP2Location </a> web service WS9 Package only.';
    }

    /**
     * @return string
     */
    protected function getUrl()
    {
        return "api.ip2location.com/?ip={$this->ip}&key={$this->auth}&package=WS9&format=json";
    }

    /**
     * @param $response
     */
    protected function parseResponse($response)
    {
        try {
            $record = json_decode($response);
            if (isset($record->country_name)) {
                $this->country   = $record->country_name;
                $this->region    = $record->region_name;
                $this->city      = $record->city_name;
                $this->latitude  = $record->latitude;
                $this->longitude = $record->longitude;
                //$this->timezone  = $record->location->timeZone;
                $this->zipcode = $record->zip_code;
            }
        } catch (\Exception $exception) {
            if ($this->logger) {
                $this->logger->warn('IP LOOKUP: '.$exception->getMessage());
            }
        }
    }
}
