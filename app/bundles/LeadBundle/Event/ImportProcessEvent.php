<?php

declare(strict_types=1);

/*
 * @copyright   2019 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Event;

use Mautic\LeadBundle\Entity\Import;
use Mautic\LeadBundle\Entity\LeadEventLog;
use Symfony\Component\EventDispatcher\Event;

final class ImportProcessEvent extends Event
{
    /**
     * @var Import
     */
    private $import;

    /**
     * @var LeadEventLog
     */
    private $eventLog;

    /**
     * @var array
     */
    private $rowData;

    /**
     * @var bool
     */
    private $wasMerged;

    public function __construct(Import $import, LeadEventLog $eventLog, array $rowData)
    {
        $this->import   = $import;
        $this->eventLog = $eventLog;
        $this->rowData  = $rowData;
    }

    public function getImport(): Import
    {
        return $this->import;
    }

    public function getEventLog(): LeadEventLog
    {
        return $this->eventLog;
    }

    public function getRowData(): array
    {
        return $this->rowData;
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
