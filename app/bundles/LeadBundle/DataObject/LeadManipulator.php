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
    /**
     * @var string|null
     */
    private $bundleName;

    /**
     * @var string|null
     */
    private $objectName;

    /**
     * @var int|null
     */
    private $objectId;

    /**
     * @var string|null
     */
    private $objectDescription;

    /**
     * LeadManipulator constructor.
     *
     * @param null $bundleName
     * @param null $objectName
     * @param null $objectId
     * @param null $objectDescription
     */
    public function __construct($bundleName = null, $objectName = null, $objectId = null, $objectDescription = null)
    {
        $this->bundleName        = $bundleName;
        $this->objectName        = $objectName;
        $this->objectId          = $objectId;
        $this->objectDescription = $objectDescription;
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

    public function getObjectDescription()
    {
        return $this->objectDescription;
    }
}
