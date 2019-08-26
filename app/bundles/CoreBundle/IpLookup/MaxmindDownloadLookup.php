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

use GeoIp2\Database\Reader;

class MaxmindDownloadLookup extends AbstractLocalDataLookup
{
    /**
     * @return string
     */
    public function getAttribution()
    {
        return 'Free lookup that leverages GeoLite2 data created by MaxMind, available from <a href="https://maxmind.com" target="_blank">maxmind.com</a>. Databases must be downloaded and periodically updated.';
    }

    /**
     * @return string
     */
    public function getLocalDataStoreFilepath()
    {
        return $this->getDataDir().'/GeoLite2-City.mmdb';
    }

    /**
     * @return string
     */
    public function getRemoteDateStoreDownloadUrl()
    {
        return 'http://geolite.maxmind.com/download/geoip/database/GeoLite2-City.mmdb.gz';
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
        } catch (\Exception $exception) {
        }
    }
}
