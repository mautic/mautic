<?php
/**
 * @package     Mautic
 * @copyright   2015 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\IpLookup;


class GeobytesIpLookup extends AbstractIpLookup
{
    /**
     * @return string
     */
    protected function getUrl()
    {
        return "http://getcitydetails.geobytes.com/GetCityDetails?fqcn={$this->ip}";
    }

    /**
     * @param $response
     */
    public function parseData($response)
    {
        $data = json_decode($response);
        foreach ($data as $key => $value) {
            $key = str_replace('geobytes', '', $key);
            $this->$key = $value;
        }
    }
}