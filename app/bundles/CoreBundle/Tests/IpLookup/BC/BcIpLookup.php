<?php
/**
 * @package     Mautic
 * @copyright   2015 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Tests\IpLookup\BC;

use Mautic\CoreBundle\IpLookup\AbstractIpLookup;

class BcIpLookup extends AbstractIpLookup
{
    /**
     * @return string
     */
    protected function getUrl()
    {
        return "http://localhost/json/{$this->ip}";
    }

    /**
     * @param $response
     */
    public function parseData($response)
    {
        $this->city = 'San Francisco';
    }
}