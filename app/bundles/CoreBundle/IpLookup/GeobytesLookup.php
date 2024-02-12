<?php

namespace Mautic\CoreBundle\IpLookup;

class GeobytesLookup extends AbstractRemoteDataLookup
{
    public string $forwarderfor        = '';
    public string $remoteip            = '';
    public string $ipaddress           = '';
    public string $certainty           = '';
    public string $internet            = '';
    public string $regionlocationcode  = '';
    public string $code                = '';
    public string $locationcode        = '';
    public string $cityid              = '';
    public string $fqcn                = '';
    public string $capital             = '';
    public string $nationalitysingular = '';
    public string $population          = '';
    public string $nationalityplural   = '';
    public string $mapreference        = '';
    public string $currency            = '';
    public string $currencycode        = '';
    public string $title               = '';

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
