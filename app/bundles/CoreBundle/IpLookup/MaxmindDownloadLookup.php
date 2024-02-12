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
        if (!empty($this->getLicenceKey())) {
            $data                = [];
            $data['license_key'] = $this->getLicenceKey();
            $data['edition_id']  = 'GeoLite2-City';
            $data['suffix']      = 'tar.gz';
            $queryString         = http_build_query($data);

            return 'https://download.maxmind.com/app/geoip_download?'.$queryString;
        } else {
            $this->logger->warning('MaxMind license key is required.');
        }
    }

    private function getLicenceKey(): string
    {
        $auth = explode(':', $this->auth, 2);
        if (array_key_exists(1, $auth)) {
            return $auth[1];
        }

        return '';
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
