<?php
/**
 * @package     Mautic
 * @copyright   2015 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\IpLookup;


class TelizeIpLookup extends AbstractIpLookup
{
    /**
     * @return string
     */
    protected function getUrl()
    {
        return "http://www.telize.com/geoip/{$this->ip}";
    }

    /**
     * @param $response
     */
    public function parseData($response)
    {
        $data = json_decode($response);

        if ($data) {
            foreach ($data as $key => $value) {
                $this->$key = $value;
            }
        }
    }
}