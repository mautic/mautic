<?php

/*
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\IpLookup;

use IP2Location\Database;

class IP2LocationBinLookupDB7 extends AbstractLocalDataLookup
{

    /**
     * Return attribution HTML displayed in the configuration UI.
     *
     * @return string
     */
    public function getAttribution()
    {
        return 'IP2Location Local Bin File DB7BIN only';
    }

    /**
     * @return string
     */
    public function getLocalDataStoreFilepath()
    {
        return $this->getDataDir();
    }

    /**
     * @return string
     */
    public function getRemoteDateStoreDownloadUrl()
    {
        $usernamePass = explode(':', $this->auth);
        $data         = [];

        if (isset($usernamePass[0]) && isset($usernamePass[1])) {
            $data['login']       = $usernamePass[0];
            $data['password']    = $usernamePass[1];
            $data['productcode'] = 'DB7BIN';
            $queryString         = http_build_query($data);
            // the system gets the file name from end of remove file path url so use hardedcoded name
            $queryString .= '&filename=/ip2locaion.zip';

            return 'https://www.ip2location.com/download?'.$queryString;
        } else {
            $this->logger->warn('Both username and password are required');
        }
    }

    /**
     * Executes the lookup of the IP address.
     */
    protected function lookup()
    {
        try {
            $reader = new Database($this->getLocalDataStoreFilepath().'/IP-COUNTRY-REGION-CITY-ISP-DOMAIN.BIN', Database::FILE_IO);
            $record = $reader->lookup($this->ip, Database::ALL);

            if (isset($record['countryName'])) {
                $this->country      = $record['countryName'];
                $this->region       = $record['regionName'];
                $this->city         = $record['cityName'];
                $this->isp          = $record['isp'];
                $this->organization = $record['domainName'];
            }
        } catch (\Exception $exception) {
            if ($this->logger) {
                $this->logger->warn('IP LOOKUP: '.$exception->getMessage());
            }
        }
    }
}