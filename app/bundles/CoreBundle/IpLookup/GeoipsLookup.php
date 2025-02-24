<?php

namespace Mautic\CoreBundle\IpLookup;

class GeoipsLookup extends AbstractRemoteDataLookup
{
    public string $continent_name = '';
    public string $continent_code = '';
    public string $country_code   = '';
    public string $region_code    = '';
    public string $county_name    = '';

    public function getAttribution(): string
    {
        return '<a href="http://www.geoips.com/" target="_blank">GeoIPs</a> offers tiered subscriptions for lookups.';
    }

    protected function getUrl(): string
    {
        return "http://api.geoips.com/ip/{$this->ip}/key/{$this->auth}/output/json";
    }

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
