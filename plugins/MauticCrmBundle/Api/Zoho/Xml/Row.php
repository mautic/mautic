<?php

/*
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticCrmBundle\Api\Zoho\Xml;

use Mautic\PluginBundle\Helper\Cleaner;

class Row
{
    /**
     * @var
     */
    protected $id;

    /**
     * @var
     */
    protected $values = [];

    /**
     * Row constructor.
     *
     * @param $id
     */
    public function __construct($id)
    {
        $this->id = $id;
    }

    /**
     * @param $key
     * @param $value
     *
     * @return $this
     */
    public function add($key, $value)
    {
        $value          = Cleaner::clean($value);
        $this->values[] = sprintf('<FL val="%s"><![CDATA[%s]]></FL>', $key, $value);

        return $this;
    }

    /**
     * @return int
     */
    public function hasValues()
    {
        return count($this->values);
    }

    /**
     * @return string
     */
    public function write()
    {
        if (!$this->values) {
            return '';
        }

        $string = "<row no=\"{$this->id}\">\n";
        $string .= implode("\n", $this->values)."\n";
        $string .= "</row>\n";

        return $string;
    }
}
