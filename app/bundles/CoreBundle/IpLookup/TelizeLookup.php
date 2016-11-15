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
                if ($key == 'postal_code') {
                    $key = 'zipcode';
                }

                $this->$key = $value;
            }
        }
    }
}
