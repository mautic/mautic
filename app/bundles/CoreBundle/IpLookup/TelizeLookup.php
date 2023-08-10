<?php

namespace Mautic\CoreBundle\IpLookup;

class TelizeLookup extends AbstractRemoteDataLookup
{
    /**
     * @return string
     */
    public function getAttribution()
    {
        return '<a href="https://market.mashape.com/fcambus/telize/" target="_blank">Telize</a> is a paid lookup service.';
    }

    /**
     * @return string
     */
    protected function getUrl()
    {
        return "https://telize-v1.p.mashape.com/geoip/{$this->ip}";
    }

    /**
     * @return array
     */
    protected function getHeaders()
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
