<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Sync\DAO\Sync\Request;

class ObjectDAO
{
    /**
     * @var string[]
     */
    private array $fields = [];

    /**
     * @var string[]
     */
    private array $requiredFields = [];

    public function __construct(
        private string $object,
        /**
         * Date/time based on last synced date for the object or the start date/time fed through the command's arguments.
         * This value does not change between iterations.
         */
        private ?\DateTimeInterface $fromDateTime = null,
        /**
         * Date/Time the sync started.
         */
        private ?\DateTimeInterface $toDateTime = null,
        /**
         * @deprecated Not used. To be removed in Mautic 6. Use SyncDateHelper instead
         */
        private ?\DateTimeInterface $objectLastSyncDateTime = null,
    ) {
    }

    public function getObject(): string
    {
        return $this->object;
    }

    /**
     * @return self
     */
    public function addField(string $field)
    {
        $this->fields[] = $field;

        return $this;
    }

    /**
     * @return string[]
     */
    public function getFields(): array
    {
        return $this->fields;
    }

    public function setRequiredFields(array $fields): void
    {
        $this->requiredFields = $fields;
    }

    /**
     * @return string[]
     */
    public function getRequiredFields(): array
    {
        return $this->requiredFields;
    }

    public function getFromDateTime(): ?\DateTimeInterface
    {
        return $this->fromDateTime;
    }

    public function getToDateTime(): ?\DateTimeInterface
    {
        return $this->toDateTime;
    }

    /**
     * @deprecated Not used. To be removed in Mautic 6. Use SyncDateHelper instead
     */
    public function getObjectLastSyncDateTime(): ?\DateTimeInterface
    {
        return $this->objectLastSyncDateTime;
    }
}
