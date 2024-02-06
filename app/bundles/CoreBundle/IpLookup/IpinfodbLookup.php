<?php

namespace Mautic\CoreBundle\IpLookup;

class IpinfodbLookup extends AbstractRemoteDataLookup
{
    public function getAttribution(): string
    {
        return '<a href="http://www.ipinfodb.com/" target="_blank">iPInfoDB</a> offers a free service (2 lookups per second) that leverages data from IP2Location. API key required.';
    }

    protected function getUrl(): string
    {
        return "http://api.ipinfodb.com/v3/ip-city/?key={$this->auth}&format=json&ip={$this->ip}";
    }

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
