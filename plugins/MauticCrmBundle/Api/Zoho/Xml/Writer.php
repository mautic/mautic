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

class Writer
{
    /**
     * @var XmlObject
     */
    protected $object;

    /**
     * @var Row
     */
    protected $row;

    /**
     * Writer constructor.
     *
     * @param $object
     */
    public function __construct($object)
    {
        $this->object = new XmlObject($object);

        return $this;
    }

    /**
     * @param $id
     *
     * @return Row
     */
    public function row($id)
    {
        if ($this->row) {
            $this->object->add($this->row);
        }

        // Reset the row
        $this->row = new Row($id);

        return $this->row;
    }

    /**
     * @return string
     */
    public function write()
    {
        // Write the last row
        $this->object->add($this->row);

        return $this->object->write();
    }
}
