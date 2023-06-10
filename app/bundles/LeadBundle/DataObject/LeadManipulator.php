<?php

namespace Mautic\LeadBundle\DataObject;

class LeadManipulator
{
    /**
     * If true then the manipulator was logged and should not be logged for the second time.
     *
     * @var bool
     */
    private $logged = false;

    public function __construct(private ?string $bundleName = null, private ?string $objectName = null, private ?int $objectId = null, private ?string $objectDescription = null)
    {
    }

    public function getBundleName(): ?string
    {
        return $this->bundleName;
    }

    public function getObjectName(): ?string
    {
        return $this->objectName;
    }

    public function getObjectId(): ?int
    {
        return $this->objectId;
    }

    public function getObjectDescription(): ?string
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
