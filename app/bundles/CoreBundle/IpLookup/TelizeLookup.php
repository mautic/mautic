<?php

namespace Mautic\CoreBundle\IpLookup;

class TelizeLookup extends AbstractRemoteDataLookup
{
    public string $offset         = '';
    public string $area_code      = '';
    public string $dma_code       = '';
    public string $country_code3  = '';
    public string $continent_code = '';
    public string $country_code   = '';
    public string $region_code    = '';

    public function getAttribution(): string
    {
        return '<a href="https://market.mashape.com/fcambus/telize/" target="_blank">Telize</a> is a paid lookup service.';
    }

    protected function getUrl(): string
    {
        return "https://telize-v1.p.mashape.com/geoip/{$this->ip}";
    }

    protected function getHeaders(): array
    {
        return [
            'X-Mashape-Key' => $this->auth,
            'Accept'        => 'application/json',
        ];
    }

    /**
     * Populates properties with obtained data from the service.
     *
     * @param mixed $response Response from the service
     */
    protected function parseResponse($response)
    {
        $data = json_decode($response);

        if ($data) {
            foreach ($data as $key => $value) {
                if ('postal_code' == $key) {
                    $key = 'zipcode';
                }

                $this->$key = $value;
            }
        }
    }
}
