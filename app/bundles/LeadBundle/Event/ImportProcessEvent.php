<?php

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

class ImportProcessEvent extends Event
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

    /**
     * @return Import
     */
    public function getImport()
    {
        return $this->import;
    }

    /**
     * @return LeadEventLog
     */
    public function getEventLog()
    {
        return $this->eventLog;
    }

    /**
     * @return array
     */
    public function getRowData()
    {
        return $this->rowData;
    }

    /**
     * @param bool $wasMerged
     */
    public function setWasMerged($wasMerged)
    {
        $this->wasMerged = $wasMerged;
    }

    /**
     * @return bool
     *
     * @throws \UnexpectedValueException
     */
    public function wasMerged()
    {
        if (null === $this->wasMerged) {
            throw new \UnexpectedValueException("Import failed as {$this->import->getObject()} object is missing import handler.");
        }

        return $this->wasMerged;
    }

    /**
     * @param string $object
     *
     * @return bool
     */
    public function importIsForObject($object)
    {
        return $this->import->getObject() === $object;
    }
}
