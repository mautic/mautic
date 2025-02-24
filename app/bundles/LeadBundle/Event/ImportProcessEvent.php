<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Event;

use Mautic\CoreBundle\Event\CommonEvent;
use Mautic\LeadBundle\Entity\Import;
use Mautic\LeadBundle\Entity\LeadEventLog;

final class ImportProcessEvent extends CommonEvent
{
    private ?bool $wasMerged = null;

    public function __construct(
        public Import $import,
        public LeadEventLog $eventLog,
        public array $rowData
    ) {
    }

    public function setWasMerged(bool $wasMerged): void
    {
        $this->wasMerged = $wasMerged;
    }

    /**
     * @throws \UnexpectedValueException
     */
    public function wasMerged(): bool
    {
        if (null === $this->wasMerged) {
            throw new \UnexpectedValueException("Import failed as {$this->import->getObject()} object is missing import handler.");
        }

        return $this->wasMerged;
    }

    public function importIsForObject(string $object): bool
    {
        return $this->import->getObject() === $object;
    }
}
