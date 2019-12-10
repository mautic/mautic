<?php

declare(strict_types=1);

/*
 * @copyright   2019 Mautic Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://www.mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\IntegrationsBundle\Event;

use MauticPlugin\IntegrationsBundle\Sync\DAO\DateRange;
use MauticPlugin\IntegrationsBundle\Sync\SyncDataExchange\Internal\Object\ObjectInterface;
use Symfony\Component\EventDispatcher\Event;

class InternalObjectFindEvent extends Event
{
    /**
     * @var ObjectInterface
     */
    private $object;

    /**
     * @var int[]
     */
    private $ids = [];

    /**
     * @var array
     */
    private $foundObjects = [];

    /**
     * @var DateRange|null
     */
    private $dateRange;

    /**
     * @var int|null
     */
    private $start;

    /**
     * @var int|null
     */
    private $limit;

    /**
     * @var array
     */
    private $fieldValues = [];

    /**
     * @param ObjectInterface $object
     */
    public function __construct(ObjectInterface $object)
    {
        $this->object = $object;
    }

    /**
     * @return ObjectInterface
     */
    public function getObject(): ObjectInterface
    {
        return $this->object;
    }

    /**
     * @return int[]
     */
    public function getIds(): array
    {
        return $this->ids;
    }

    /**
     * @param int[] $ids
     */
    public function setIds(array $ids): void
    {
        $this->ids = $ids;
    }

    /**
     * @return array
     */
    public function getFoundObjects(): array
    {
        return $this->foundObjects;
    }

    /**
     * @param array $foundObjects
     */
    public function setFoundObjects(array $foundObjects): void
    {
        $this->foundObjects = $foundObjects;
    }

    /**
     * @return DateRange|null
     */
    public function getDateRange(): ?DateRange
    {
        return $this->dateRange;
    }

    /**
     * @param DateRange|null $dateRange
     */
    public function setDateRange(?DateRange $dateRange): void
    {
        $this->dateRange = $dateRange;
    }

    /**
     * @return int|null
     */
    public function getStart(): ?int
    {
        return $this->start;
    }

    /**
     * @param int|null $start
     */
    public function setStart(?int $start): void
    {
        $this->start = $start;
    }

    /**
     * @return int|null
     */
    public function getLimit(): ?int
    {
        return $this->limit;
    }

    /**
     * @param int|null $limit
     */
    public function setLimit(?int $limit): void
    {
        $this->limit = $limit;
    }

    /**
     * @return array
     */
    public function getFieldValues(): array
    {
        return $this->fieldValues;
    }

    /**
     * @param array $fieldValues
     */
    public function setFieldValues(array $fieldValues): void
    {
        $this->fieldValues = $fieldValues;
    }
}
