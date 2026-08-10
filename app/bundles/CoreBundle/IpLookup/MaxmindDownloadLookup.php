<?php

namespace Mautic\CoreBundle\IpLookup;

use GeoIp2\Database\Reader;

class MaxmindDownloadLookup extends AbstractLocalDataLookup
{
    public function getAttribution(): string
    {
        return 'Free lookup that leverages GeoLite2 data created by MaxMind, available from <a href="https://maxmind.com" target="_blank">maxmind.com</a>. Databases must be downloaded and periodically updated.';
    }

    public function getLocalDataStoreFilepath(): string
    {
        return $this->getDataDir().'/GeoLite2-City.mmdb';
    }

    /**
     * @return string
     */
    public function getRemoteDateStoreDownloadUrl()
    {
        $baseAuth = $this->getLicenceKey();

        if (null === $baseAuth || '' === $baseAuth) {
            $this->logger->warning('MaxMind license key is required.');

            return '';
        }

        $data = [
            'suffix' => 'tar.gz',
        ];
        $queryString = http_build_query($data);

        return 'https://'.$baseAuth.'@download.maxmind.com/geoip/databases/GeoLite2-City/download?'.$queryString;
    }

    private function getLicenceKey(): ?string
    {
        if (null === $this->auth) {
            return null;
        }

        if (1 !== preg_match('/^\d+:[a-z0-9_]+$/i', $this->auth)) {
            return '';
        }

        return $this->auth;
    }

    /**
     * Extract the IP from the local database.
     */
    protected function lookup()
    {
        try {
            $reader = new Reader($this->getLocalDataStoreFilepath());
            $record = $reader->city($this->ip);

            if (isset($record->subdivisions[0])) {
                if (count($record->subdivisions) > 1) {
                    // Use the first listed as the country and second as state
                    // UK -> England -> Winchester
                    $this->country = $record->subdivisions[0]->name;
                    $this->region  = $record->subdivisions[1]->name;
                } else {
                    $this->region = $record->subdivisions[0]->name;
                }
            }

            $this->city      = $record->city->name;
            $this->country   = $record->country->name;
            $this->latitude  = $record->location->latitude;
            $this->longitude = $record->location->longitude;
            $this->timezone  = $record->location->timeZone;
            $this->zipcode   = $record->location->postalCode;
        } catch (\Exception) {
        }
    }
}
