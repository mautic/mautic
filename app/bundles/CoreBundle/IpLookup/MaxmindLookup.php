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

/**
 * Class MaxmindLookup.
 */
class MaxmindLookup extends AbstractRemoteDataLookup
{
    /**
     * @return string
     */
    public function getAttribution()
    {
        return '<a href="https://www.maxmind.com/en/geoip2-precision-services" target="_blank">MaxMind Precision Services</a> is a pay per query lookup service that offers solutions with multiple levels of accuracy and details.';
    }

    /**
     * @return array
     */
    protected function getHeaders()
    {
        return ['Authorization' => 'Basic '.base64_encode($this->auth)];
    }

    /**
     * @return string
     */
    protected function getUrl()
    {
        $url = 'https://geoip.maxmind.com/geoip/v2.1/';

        switch ($this->getName()) {
            case 'maxmind_country':
                $url .= 'country';
                break;
            case 'maxmind_precision':
                $url .= 'city';
                break;
            case 'maxmind_omni':
                $url .= 'insights';
                break;
        }

        $url .= "/{$this->ip}";

        return $url;
    }

    /**
     * @param $response
     */
    protected function parseResponse($response)
    {
        $data = json_decode($response);

        if ($data) {
            if (empty($data->error)) {
                if (isset($data->postal)) {
                    $this->zipcode = $data->postal->code;
                }
                $this->country = $data->country->names->en;
                $this->city    = $data->city->names->en;

                if (isset($data->subdivisions[0])) {
                    if (count($data->subdivisions) > 1) {
                        // Use the first listed as the country and second as state
                        // UK -> England -> Winchester
                        $this->country = $data->subdivisions[0]->names->en;
                        $this->region  = $data->subdivisions[1]->names->en;
                    } else {
                        $this->region = $data->subdivisions[0]->names->en;
                    }
                }

                $this->latitude  = $data->location->latitude;
                $this->longitude = $data->location->longitude;
                $this->timezone  = $data->location->time_zone;

                if (isset($data->traits->isp)) {
                    $this->isp = $data->traits->isp;
                }

                if (isset($data->traits->organization)) {
                    $this->organization = $data->traits->organization;
                }
            } elseif (null !== $this->logger) {
                $this->logger->warning('IP LOOKUP: '.$data->error);
            }
        }
    }
}
