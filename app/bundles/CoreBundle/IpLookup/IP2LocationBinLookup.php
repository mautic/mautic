<?php
/**
 * @package     Mautic
 * @copyright   2015 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
*/
/*
	Pelbox Solutions
*/
namespace Mautic\CoreBundle\IpLookup;
use IP2Location;
class IP2LocationBinLookup extends AbstractLocalDataLookup
{
    /**
     * @return string
     */
    public function getAttribution()
    {
        return 'IP2Location Local Bin File DB9BIN only';
    }

    /**
     * @return string
     */
	 
    public function getLocalDataStoreFilepath()
    {  return $this->getDataDir();
    }

    /**
     * @return string
     */
    public function getRemoteDateStoreDownloadUrl()
    {
	   $usernamePass = explode(':',$this->auth);
	   $data= array();
	   $data['login'] = $usernamePass[0];
	   $data['password'] = $usernamePass[1];
	   $data['productcode'] = 'DB9BIN';
       $queryString =  http_build_query($data);
	   // the system gets the file name from end of remove file path url so use hardedcoded name
	    $queryString .='&filename=/ip2locaion.zip';
	   return 'https://www.ip2location.com/download?'.$queryString;
	   
    }

    /**
     * Extract the IP from the local database
     */
    protected function lookup()
    {
		

        try {
			$reader = new IP2Location($this->getLocalDataStoreFilepath().'/IP-COUNTRY-REGION-CITY-LATITUDE-LONGITUDE-ZIPCODE.BIN', IP2Location::FILE_IO);
			
			$record = $reader->lookup($this->ip, IP2Location::ALL);
			
			if(isset($record->countryName)){
				$this->country = $record->countryName;
				$this->region = $record->regionName;
				$this->city = $record->cityName;
				$this->latitude  = $record->latitude;
				$this->longitude = $record->longitude;
				//$this->timezone  = $record->location->timeZone;
				$this->zipcode   = $records->zipCode;
			}
        } catch (\Exception $exception) {
            if ($this->logger) {
                $this->logger->warn("IP LOOKUP: " . $exception->getMessage());
            }
        }
    }
} 


