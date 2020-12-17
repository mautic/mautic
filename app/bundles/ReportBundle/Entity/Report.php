<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ReportBundle\Entity;

use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Mapping as ORM;
use Mautic\ApiBundle\Serializer\Driver\ApiMetadataDriver;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Mautic\CoreBundle\Entity\FormEntity;
use Mautic\EmailBundle\Validator as EmailAssert;
use Mautic\ReportBundle\Scheduler\Enum\SchedulerEnum;
use Mautic\ReportBundle\Scheduler\Exception\ScheduleNotValidException;
use Mautic\ReportBundle\Scheduler\SchedulerInterface;
use Mautic\ReportBundle\Scheduler\Validator as ReportAssert;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Mapping\ClassMetadata;

class Report extends FormEntity implements SchedulerInterface
{
    /**
     * @var int
     */
    private $id;

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $description;

    /**
     * @var bool
     */
    private $system = false;

    /**
     * @var string
     */
    private $source;

    /**
     * @var array
     */
    private $columns = [];

    /**
     * @var array
     */
    private $filters = [];

    /**
     * @var array
     */
    private $tableOrder = [];

    /**
     * @var array
     */
    private $graphs = [];

    /**
     * @var array
     */
    private $groupBy = [];

    /**
     * @var array
     */
    private $aggregators = [];

    /**
     * @var array
     */
    private $settings = [];

    /**
     * @var bool
     */
    private $isScheduled = false;

    /**
     * @var string|null
     */
    private $toAddress;

    /**
     * @var string|null
     */
    private $scheduleUnit;

    /**
     * @var string|null
     */
    private $scheduleDay;

    /**
     * @var string|null
     */
    private $scheduleMonthFrequency;

    public function __clone()
    {
        $this->id = null;

        parent::__clone();
    }

    public static function loadMetadata(ORM\ClassMetadata $metadata)
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable('reports')
            ->setCustomRepositoryClass(ReportRepository::class);

        $builder->addIdColumns();

        $builder->addField('system', Type::BOOLEAN, ['columnName'=>'`system`']);

        $builder->addField('source', Type::STRING);

        $builder->createField('columns', Type::TARRAY)
            ->nullable()
            ->build();

        $builder->createField('filters', Type::TARRAY)
            ->nullable()
            ->build();

        $builder->createField('tableOrder', Type::TARRAY)
            ->columnName('table_order')
            ->nullable()
            ->build();

        $builder->createField('graphs', Type::TARRAY)
            ->nullable()
            ->build();

        $builder->createField('groupBy', Type::TARRAY)
            ->columnName('group_by')
            ->nullable()
            ->build();

        $builder->createField('aggregators', Type::TARRAY)
            ->columnName('aggregators')
            ->nullable()
            ->build();

        $builder->createField('settings', Type::JSON_ARRAY)
            ->columnName('settings')
            ->nullable()
            ->build();

        $builder->createField('isScheduled', Type::BOOLEAN)
            ->columnName('is_scheduled')
            ->build();

        $builder->addNullableField('scheduleUnit', Type::STRING, 'schedule_unit');
        $builder->addNullableField('toAddress', Type::STRING, 'to_address');
        $builder->addNullableField('scheduleDay', Type::STRING, 'schedule_day');
        $builder->addNullableField('scheduleMonthFrequency', Type::STRING, 'schedule_month_frequency');
    }

    public static function loadValidatorMetadata(ClassMetadata $metadata)
    {
        $metadata->addPropertyConstraint('name', new NotBlank([
            'message' => 'mautic.core.name.required',
        ]));

        $metadata->addPropertyConstraint('toAddress', new EmailAssert\MultipleEmailsValid());

        $metadata->addConstraint(new ReportAssert\ScheduleIsValid());
    }

    /**
     * Prepares the metadata for API usage.
     *
     * @param $metadata
     */
    public static function loadApiMetadata(ApiMetadataDriver $metadata)
    {
        $metadata->setGroupPrefix('report')
            ->addListProperties(
                [
                    'id',
                    'name',
                    'description',
                    'system',
                    'isScheduled',
                ]
            )
            ->addProperties(
                [
                    'source',
                    'columns',
                    'filters',
                    'tableOrder',
                    'graphs',
                    'groupBy',
                    'settings',
                    'aggregators',
                    'scheduleUnit',
                    'toAddress',
                    'scheduleDay',
                    'scheduleMonthFrequency',
                ]
            )
            ->build();
    }

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    public function setId(?int $id): void
    {
        $this->id = $id;
    }

    /**
     * Set name.
     *
     * @param string $name
     *
     * @return Report
     */
    public function setName($name)
    {
        $this->isChanged('name', $name);
        $this->name = $name;

        return $this;
    }

    /**
     * Get name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set system.
     *
     * @param string $system
     *
     * @return Report
     */
    public function setSystem($system)
    {
        $this->isChanged('system', $system);
        $this->system = $system;

        return $this;
    }

    /**
     * Get system.
     *
     * @return int
     */
    public function getSystem()
    {
        return $this->system;
    }

    /**
     * Set source.
     *
     * @param string $source
     *
     * @return Report
     */
    public function setSource($source)
    {
        $this->isChanged('source', $source);
        $this->source = $source;

        return $this;
    }

    /**
     * Get source.
     *
     * @return string
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * Set columns.
     *
     * @param string $columns
     *
     * @return Report
     */
    public function setColumns($columns)
    {
        $this->isChanged('columns', $columns);
        $this->columns = $columns;

        return $this;
    }

    /**
     * Get columns.
     *
     * @return array
     */
    public function getColumns()
    {
        return $this->columns;
    }

    /**
     * Set filters.
     *
     * @param string $filters
     *
     * @return Report
     */
    public function setFilters($filters)
    {
        $this->isChanged('filters', $filters);
        $this->filters = $filters;

        return $this;
    }

    /**
     * Get filters.
     *
     * @return array
     */
    public function getFilters()
    {
        return $this->filters;
    }

    /**
     * Get filter value from a specific filter.
     *
     * @param string $column
     *
     * @return mixed
     *
     * @throws \UnexpectedValueException
     */
    public function getFilterValue($column)
    {
        foreach ($this->getFilters() as $field) {
            if ($column === $field['column']) {
                return $field['value'];
            }
        }

        throw new \UnexpectedValueException("Column {$column} doesn't have any filter.");
    }

    /**
     * Get filter values from a specific filter.
     *
     * @param string $column
     *
     * @return array
     *
     * @throws \UnexpectedValueException
     */
    public function getFilterValues($column)
    {
        $values = [];
        foreach ($this->getFilters() as $field) {
            if ($column === $field['column']) {
                $values[] = $field['value'];
            }
        }

        if (empty($values)) {
            throw new \UnexpectedValueException("Column {$column} doesn't have any filter.");
        }

        return $values;
    }

    /**
     * @return mixed
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param mixed $description
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }

    /**
     * @return mixed
     */
    public function getTableOrder()
    {
        return $this->tableOrder;
    }

    public function setTableOrder(array $tableOrder)
    {
        $this->isChanged('tableOrder', $tableOrder);

        $this->tableOrder = $tableOrder;
    }

    /**
     * @return mixed
     */
    public function getGraphs()
    {
        return $this->graphs;
    }

    public function setGraphs(array $graphs)
    {
        $this->isChanged('graphs', $graphs);

        $this->graphs = $graphs;
    }

    /**
     * @return mixed
     */
    public function getGroupBy()
    {
        return $this->groupBy;
    }

    public function setGroupBy(array $groupBy)
    {
        $this->isChanged('groupBy', $groupBy);

        $this->groupBy = $groupBy;
    }

    /**
     * @return mixed
     */
    public function getAggregators()
    {
        return $this->aggregators;
    }

    /**
     * @return array
     */
    public function getAggregatorColumns()
    {
        return array_map(function ($aggregator) {
            return $aggregator['column'];
        }, $this->getAggregators());
    }

    /**
     * @return array
     */
    public function getOrderColumns()
    {
        return array_map(function ($order) {
            return $order['column'];
        }, $this->getTableOrder());
    }

    /**
     * @return array
     */
    public function getSelectAndAggregatorAndOrderAndGroupByColumns()
    {
        return array_merge($this->getSelectAndAggregatorColumns(), $this->getOrderColumns(), $this->getGroupBy());
    }

    /**
     * @return array
     */
    public function getSelectAndAggregatorColumns()
    {
        return array_merge($this->getColumns(), $this->getAggregatorColumns());
    }

    public function setAggregators(array $aggregators)
    {
        $this->isChanged('aggregators', $aggregators);

        $this->aggregators = $aggregators;
    }

    public function setSettings(array $settings)
    {
        $this->isChanged('settings', $settings);

        $this->settings = $settings;
    }

    /**
     * @return array
     */
    public function getSettings()
    {
        return $this->settings;
    }

    /**
     * @return bool
     */
    public function isScheduled()
    {
        return $this->isScheduled;
    }

    /**
     * @param bool $isScheduled
     */
    public function setIsScheduled($isScheduled)
    {
        $this->isChanged('isScheduled', $isScheduled);

        $this->isScheduled = $isScheduled;
    }

    /**
     * @return string|null
     */
    public function getToAddress()
    {
        return $this->toAddress;
    }

    /**
     * @param string|null $toAddress
     */
    public function setToAddress($toAddress)
    {
        $this->isChanged('toAddress', $toAddress);

        $this->toAddress = $toAddress;
    }

    /**
     * @return string|null
     */
    public function getScheduleUnit()
    {
        return $this->scheduleUnit;
    }

    /**
     * @param string|null $scheduleUnit
     */
    public function setScheduleUnit($scheduleUnit)
    {
        $this->isChanged('scheduleUnit', $scheduleUnit);

        $this->scheduleUnit = $scheduleUnit;
    }

    /**
     * @return string|null
     */
    public function getScheduleDay()
    {
        return $this->scheduleDay;
    }

    /**
     * @param string|null $scheduleDay
     */
    public function setScheduleDay($scheduleDay)
    {
        $this->isChanged('scheduleDay', $scheduleDay);

        $this->scheduleDay = $scheduleDay;
    }

    /**
     * @return string|null
     */
    public function getScheduleMonthFrequency()
    {
        return $this->scheduleMonthFrequency;
    }

    /**
     * @param string|null $scheduleMonthFrequency
     */
    public function setScheduleMonthFrequency($scheduleMonthFrequency)
    {
        $this->scheduleMonthFrequency = $scheduleMonthFrequency;
    }

    public function setAsNotScheduled()
    {
        $this->setIsScheduled(false);
        $this->setToAddress(null);
        $this->setScheduleUnit(null);
        $this->setScheduleDay(null);
        $this->setScheduleMonthFrequency(null);
    }

    public function setAsScheduledNow(string $email): void
    {
        $this->setIsScheduled(true);
        $this->setToAddress($email);
        $this->setScheduleUnit(SchedulerEnum::UNIT_NOW);
    }

    public function ensureIsDailyScheduled()
    {
        $this->setIsScheduled(true);
        $this->setScheduleUnit(SchedulerEnum::UNIT_DAILY);
        $this->setScheduleDay(null);
        $this->setScheduleMonthFrequency(null);
    }

    /**
     * @throws ScheduleNotValidException
     */
    public function ensureIsMonthlyScheduled()
    {
        if (
            !in_array($this->getScheduleMonthFrequency(), SchedulerEnum::getMonthFrequencyForSelect()) ||
            !in_array($this->getScheduleDay(), SchedulerEnum::getDayEnumForSelect())
        ) {
            throw new ScheduleNotValidException();
        }
        $this->setIsScheduled(true);
        $this->setScheduleUnit(SchedulerEnum::UNIT_MONTHLY);
    }

    /**
     * @throws ScheduleNotValidException
     */
    public function ensureIsWeeklyScheduled()
    {
        if (!in_array($this->getScheduleDay(), SchedulerEnum::getDayEnumForSelect())) {
            throw new ScheduleNotValidException();
        }
        $this->setIsScheduled(true);
        $this->setScheduleUnit(SchedulerEnum::UNIT_WEEKLY);
        $this->setScheduleMonthFrequency(null);
    }

    public function isScheduledNow(): bool
    {
        return SchedulerEnum::UNIT_NOW === $this->getScheduleUnit();
    }

    /**
     * @return bool
     */
    public function isScheduledDaily()
    {
        return SchedulerEnum::UNIT_DAILY === $this->getScheduleUnit();
    }

    /**
     * @return bool
     */
    public function isScheduledWeekly()
    {
        return SchedulerEnum::UNIT_WEEKLY === $this->getScheduleUnit();
    }

    /**
     * @return bool
     */
    public function isScheduledMonthly()
    {
        return SchedulerEnum::UNIT_MONTHLY === $this->getScheduleUnit();
    }

    /**
     * @return bool
     */
    public function isScheduledWeekDays()
    {
        return SchedulerEnum::DAY_WEEK_DAYS === $this->getScheduleDay();
    }
}
