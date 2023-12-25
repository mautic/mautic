<?php

namespace Mautic\CoreBundle\IpLookup;

class IP2LocationIoLookup extends AbstractRemoteDataLookup
{
    public function getAttribution(): string
    {
        return '<a href="https://www.ip2location.io/" target="_blank">IP2Location.io</a> Geolocation API service.';
    }

    protected function getUrl(): string
    {
        return "https://api.ip2location.io/?ip={$this->ip}&key={$this->auth}&format=json";
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
                $this->zipcode   = $record->zip_code;
            }
        } catch (\Exception $exception) {
            if ($this->logger) {
                $this->logger->warn('IP LOOKUP: '.$exception->getMessage());
            }
        }
    }
}
