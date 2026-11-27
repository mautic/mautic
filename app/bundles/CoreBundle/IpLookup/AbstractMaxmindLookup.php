<?php

namespace Mautic\CoreBundle\IpLookup;

use Mautic\CoreBundle\IpLookup\DoNotSellList\MaxMindDoNotSellList;

abstract class AbstractMaxmindLookup extends AbstractRemoteDataLookup
{
    public function getAttribution(): string
    {
        return '<a href="https://www.maxmind.com/en/geoip2-precision-services" target="_blank">MaxMind Precision Services</a> is a pay per query lookup service that offers solutions with multiple levels of accuracy and details.';
    }

    abstract protected function getName(): string;

    protected function getHeaders(): array
    {
        if (!$this->auth) {
            throw new \InvalidArgumentException('Maxmind Authentication key canot be empty.');
        }

        return ['Authorization' => 'Basic '.base64_encode($this->auth)];
    }

    protected function getUrl(): string
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

    protected function parseResponse($response): void
    {
        /**
         * @var object{
         *     error?: string,
         *     postal?: object{code: string},
         *     country?: object{names: object{en: string}},
         *     city?: object{names: object{en: string}},
         *     subdivisions?: list<object{names: object{en: string}}>,
         *     location?: object{latitude?: float, longitude?: float, time_zone?: string},
         *     traits?: object{isp?: string, organization?: string}
         * }|null $data
         */
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

                $this->latitude  = '';
                $this->longitude = '';
                $this->timezone  = '';
                if (isset($data->location)) {
                    $location = $data->location;
                    if (isset($location->latitude)) {
                        $this->latitude = $location->latitude;
                    }
                    if (isset($location->longitude)) {
                        $this->longitude = $location->longitude;
                    }
                    if (isset($location->time_zone)) {
                        $this->timezone = $location->time_zone;
                    }
                }

                if (isset($data->traits)) {
                    $traits = $data->traits;
                    if (isset($traits->isp)) {
                        $this->isp = $traits->isp;
                    }
                    if (isset($traits->organization)) {
                        $this->organization = $traits->organization;
                    }
                }
            } elseif (null !== $this->logger) {
                $this->logger->warning('IP LOOKUP: '.$data->error);
            }
        }
    }

    protected function shouldPerformLookup(): bool
    {
        if (null === $this->ip) {
            return false;
        }

        $doNotSellList = new MaxMindDoNotSellList($this->coreParametersHelper);

        $ip = $this->ip;
        $doNotSellList->loadList();
        $ipMatch = array_filter($doNotSellList->getList(), fn (array $item): bool => $doNotSellList->stripCIDR($item['value']) == $ip);

        return !boolval(count($ipMatch));
    }
}
