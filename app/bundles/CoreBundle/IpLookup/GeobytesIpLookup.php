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
        return "http://www.geobytes.com/IpLocator.htm?GetLocation&template=php3.txt&IpAddress={$this->ip}";
    }

    /**
     * {@inheritdoc}
     */
    public function getData()
    {
        $tags = get_meta_tags($this->getUrl());

        if ($tags && $tags['city'] != 'Limit Exceeded') {
            $this->parseData($tags);
        }
    }

    /**
     * @param  $data
     */
    public function parseData($data)
    {
        foreach ($data as $key => $value) {
            $this->$key = $value;
        }
    }
}