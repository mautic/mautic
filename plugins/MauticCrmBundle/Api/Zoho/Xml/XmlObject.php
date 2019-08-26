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

class XmlObject
{
    /**
     * @var
     */
    protected $object;

    /**
     * @var Row[]
     */
    protected $rows = [];

    /**
     * XmlObject constructor.
     *
     * @param $object
     */
    public function __construct($object)
    {
        $this->object = $object;
    }

    /**
     * @param Row $row
     */
    public function add(Row $row)
    {
        $this->rows[] = $row;
    }

    /**
     * @return string
     */
    public function write()
    {
        $string = "<{$this->object}>\n";
        foreach ($this->rows as $row) {
            $string .= $row->write();
        }
        $string .= "</{$this->object}>";

        $this->rows = [];

        return $string;
    }
}
