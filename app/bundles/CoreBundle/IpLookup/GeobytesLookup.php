<?php

namespace Mautic\CoreBundle\IpLookup;

class GeobytesLookup extends AbstractRemoteDataLookup
{
    public function getAttribution(): string
    {
        return '<a href="http://geobytes.com/" target="_blank">Geobytes</a> offers both free (16,000 lookups per hour) and VIP (paid) offerings.';
    }

    protected function getUrl(): string
    {
        return "http://getcitydetails.geobytes.com/GetCityDetails?fqcn={$this->ip}";
    }

    protected function parseResponse($response)
    {
        $data = json_decode($response);
        foreach ($data as $key => $value) {
            $key        = str_replace('geobytes', '', $key);
            $this->$key = $value;
        }
    }
}
