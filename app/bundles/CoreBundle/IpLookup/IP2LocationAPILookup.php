<?php

namespace Mautic\CoreBundle\IpLookup;

class IP2LocationAPILookup extends AbstractRemoteDataLookup
{
    public function getAttribution(): string
    {
        return '<a href="http://IP2Location.com/" target="_blank">IP2Location </a> web service WS9 Package only.';
    }

    protected function getUrl(): string
    {
        return "api.ip2location.com/?ip={$this->ip}&key={$this->auth}&package=WS9&format=json";
    }

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
                // $this->timezone  = $record->location->timeZone;
                $this->zipcode = $record->zip_code;
            }
        } catch (\Exception $exception) {
            if ($this->logger) {
                $this->logger->warning('IP LOOKUP: '.$exception->getMessage());
            }
        }
    }
}
