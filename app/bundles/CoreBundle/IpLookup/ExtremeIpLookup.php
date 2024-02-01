<?php

namespace Mautic\CoreBundle\IpLookup;

class ExtremeIpLookup extends AbstractRemoteDataLookup
{
    /**
     * Return attribution HTML displayed in the configuration UI.
     */
    public function getAttribution(): string
    {
        return '<a href="https://extreme-ip-lookup.com/" target="_blank">extreme-ip-lookup.com</a> is a free lookup service that does not require an api key.';
    }

    /**
     * Get the URL to fetch data from.
     */
    protected function getUrl(): string
    {
        $auth = !empty($this->auth) ? '?key='.$this->auth : '';

        return 'https://extreme-ip-lookup.com/json/'.$this->ip.$auth;
    }

    protected function parseResponse($response)
    {
        $data = json_decode($response, true);

        if ($data) {
            foreach ($data as $key => $value) {
                switch ($key) {
                    case 'region':
                        $key = 'region';
                        break;
                    case 'country':
                        $key = 'country';
                        break;
                    case 'city':
                        $key = 'city';
                        break;
                    case 'businessName':
                        $key = 'organization';
                        break;
                }

                $this->$key = $value;
            }
        }
    }
}
