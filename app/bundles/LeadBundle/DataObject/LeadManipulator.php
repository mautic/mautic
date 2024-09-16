<?php

namespace Mautic\LeadBundle\DataObject;

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
     * If true then the manipulator was logged and should not be logged for the second time.
     *
     * @var bool
     */
    private $logged = false;

    /**
     * @param ?string $bundleName
     * @param ?string $objectName
     * @param ?int    $objectId
     * @param ?string $objectDescription
     */
    public function __construct($bundleName = null, $objectName = null, $objectId = null, $objectDescription = null)
    {
        $this->bundleName        = $bundleName;
        $this->objectName        = $objectName;
        $this->objectId          = $objectId;
        $this->objectDescription = $objectDescription;
    }

    /**
     * @return ?string
     */
    public function getBundleName()
    {
        return $this->bundleName;
    }

    /**
     * @return ?string
     */
    public function getObjectName()
    {
        return $this->objectName;
    }

    /**
     * @return ?int
     */
    public function getObjectId()
    {
        return $this->objectId;
    }

    /**
     * @return ?string
     */
    public function getObjectDescription()
    {
        return $this->objectDescription;
    }

    /**
     * Check if the manipulator was logged already or not.
     *
     * @return bool
     */
    public function wasLogged()
    {
        return $this->logged;
    }

    /**
     * Set manipulator as logged so it wouldn't be logged for the second time in the same request.
     */
    public function setAsLogged()
    {
        $this->logged = true;
    }
}
