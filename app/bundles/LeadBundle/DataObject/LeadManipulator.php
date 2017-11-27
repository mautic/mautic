<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\DataObject;

/**
 * Class LeadManipulator.
 */
class LeadManipulator
{
    /** @var string|null */
    private $bundleName;

    /** @var string|null */
    private $objectName;

    /** @var int|null */
    private $objectId;

    /**
     * LeadManipulator constructor.
     *
     * @param string|null $bundleName
     * @param string|null $objectName
     * @param int|null    $objectId
     */
    public function __construct($bundleName = null, $objectName = null, $objectId = null)
    {
        $this->bundleName = $bundleName;
        $this->objectName = $objectName;
        $this->objectId   = $objectId;
    }

    /**
     * @return string
     */
    public function getBundleName()
    {
        return $this->bundleName;
    }

    /**
     * @return string
     */
    public function getObjectName()
    {
        return $this->objectName;
    }

    /**
     * @return int
     */
    public function getObjectId()
    {
        return $this->objectId;
    }
}
