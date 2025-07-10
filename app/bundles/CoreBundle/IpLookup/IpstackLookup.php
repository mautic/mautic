<?php

namespace Mautic\CoreBundle\IpLookup;

class IpstackLookup extends AbstractRemoteDataLookup
{
    public string $country_code = '';
    public string $region_code  = '';
    public string $metro_code   = '';

    public function getAttribution(): string
    {
        return '<a href="https://ipstack.com/" target="_blank">ipstack.com</a> is a free lookup service that leverages GeoLite2 data created by MaxMind.';
    }

    protected function getUrl(): string
    {
        if (empty($this->auth)) {
            $this->logger->warning('FreeGeoIP has become IPStack and now requires an API key.');
        }

        return 'http://api.ipstack.com/'.$this->ip.'?access_key='.$this->auth.'&output=json&legacy=1';
    }

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
                    case 'zip':
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
