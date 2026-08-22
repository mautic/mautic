<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\DataObject;

final class LeadManipulator
{
    /**
     * If true then the manipulator was logged and should not be logged for the second time.
     */
    private bool $logged = false;

    public function __construct(
        private readonly ?string $bundleName = null,
        private readonly ?string $objectName = null,
        private readonly ?int $objectId = null,
        private readonly ?string $objectDescription = null,
    ) {
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
     */
    public function wasLogged(): bool
    {
        return $this->logged;
    }

    /**
     * Set manipulator as logged so it wouldn't be logged for the second time in the same request.
     */
    public function setAsLogged(): void
    {
        $this->logged = true;
    }

    public function getManipulatedBy(): string
    {
        if ($this->objectDescription) {
            return $this->objectDescription;
        }

        return $this->getManipulatorKey();
    }

    public function getManipulatorKey(): string
    {
        $objectParts = [];
        if ($this->bundleName) {
            $objectParts[] = $this->bundleName;
        }
        if ($this->objectName) {
            $objectParts[] = $this->objectName;
        }
        if ($this->objectId) {
            $objectParts[] = $this->objectId;
        }

        return implode(':', $objectParts);
    }
}
