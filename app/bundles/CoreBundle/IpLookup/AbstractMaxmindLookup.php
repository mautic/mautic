<?php

namespace Mautic\CoreBundle\IpLookup;

abstract class AbstractMaxmindLookup extends AbstractRemoteDataLookup
{
    /**
     * @return string
     */
    public function getAttribution()
    {
        return '<a href="https://www.maxmind.com/en/geoip2-precision-services" target="_blank">MaxMind Precision Services</a> is a pay per query lookup service that offers solutions with multiple levels of accuracy and details.';
    }

    abstract protected function getName(): string;

    /**
     * @return array
     */
    protected function getHeaders()
    {
        if (!$this->auth) {
            throw new \InvalidArgumentException('Maxmind Authentication key canot be empty.');
        }

        return ['Authorization' => 'Basic '.base64_encode($this->auth)];
    }

    /**
     * @return string
     */
    protected function getUrl()
    {
        $url = 'https://geoip.maxmind.com/geoip/v2.1/';

        match ($this->getName()) {
            'maxmind_country'   => $url .= 'country',
            'maxmind_precision' => $url .= 'city',
            'maxmind_omni'      => $url .= 'insights',
            default             => $url."/{$this->ip}",
        };

        return $url."/{$this->ip}";
    }

    protected function parseResponse($response)
    {
        $data = json_decode($response);

        if ($data) {
            if (empty($data->error)) {
                if (isset($data->postal)) {
                    $this->zipcode = $data->postal->code;
                }
                if (isset($data->country)) {
                    $this->country = $data->country->names->en;
                }
                if (isset($data->city)) {
                    $this->city    = $data->city->names->en;
                }

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
