<?php

namespace Mautic\ReportBundle\Entity;

use Doctrine\DBAL\Types\Types;
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
     * @var string|null
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
     * @var array|null
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

    public static function loadMetadata(ORM\ClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable('reports')
            ->setCustomRepositoryClass(ReportRepository::class);

        $builder->addIdColumns();

        $builder->addField('system', Types::BOOLEAN, ['columnName'=>'`system`']);

        $builder->addField('source', Types::STRING);

        $builder->createField('columns', Types::ARRAY)
            ->nullable()
            ->build();

        $builder->createField('filters', Types::ARRAY)
            ->nullable()
            ->build();

        $builder->createField('tableOrder', Types::ARRAY)
            ->columnName('table_order')
            ->nullable()
            ->build();

        $builder->createField('graphs', Types::ARRAY)
            ->nullable()
            ->build();

        $builder->createField('groupBy', Types::ARRAY)
            ->columnName('group_by')
            ->nullable()
            ->build();

        $builder->createField('aggregators', Types::ARRAY)
            ->columnName('aggregators')
            ->nullable()
            ->build();

        $builder->createField('settings', Types::JSON)
            ->columnName('settings')
            ->nullable()
            ->build();

        $builder->createField('isScheduled', Types::BOOLEAN)
            ->columnName('is_scheduled')
            ->build();

        $builder->addNullableField('scheduleUnit', Types::STRING, 'schedule_unit');
        $builder->addNullableField('toAddress', Types::STRING, 'to_address');
        $builder->addNullableField('scheduleDay', Types::STRING, 'schedule_day');
        $builder->addNullableField('scheduleMonthFrequency', Types::STRING, 'schedule_month_frequency');
    }

    public static function loadValidatorMetadata(ClassMetadata $metadata): void
    {
        $metadata->addPropertyConstraint('name', new NotBlank([
            'message' => 'mautic.core.name.required',
        ]));

        $metadata->addPropertyConstraint('toAddress', new EmailAssert\MultipleEmailsValid());

        $metadata->addConstraint(new ReportAssert\ScheduleIsValid());
    }

    /**
     * Prepares the metadata for API usage.
     */
    public static function loadApiMetadata(ApiMetadataDriver $metadata): void
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
     * @return string
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * @param mixed[] $columns
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
     * @return array
     */
    public function getColumns()
    {
        return $this->columns;
    }

    /**
     * @param mixed[] $filters
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
     * @throws \UnexpectedValueException
     */
    public function getFilterValues($column): array
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
    public function setDescription($description): void
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

    public function setTableOrder(array $tableOrder): void
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

    public function setGraphs(array $graphs): void
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

    public function setGroupBy(array $groupBy): void
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

    public function getAggregatorColumns(): array
    {
        return array_map(fn ($aggregator) => $aggregator['column'], $this->getAggregators());
    }

    public function getOrderColumns(): array
    {
        return array_map(fn ($order) => $order['column'], $this->getTableOrder());
    }

    public function getSelectAndAggregatorAndOrderAndGroupByColumns(): array
    {
        return array_merge($this->getSelectAndAggregatorColumns(), $this->getOrderColumns(), $this->getGroupBy());
    }

    public function getSelectAndAggregatorColumns(): array
    {
        return array_merge($this->getColumns(), $this->getAggregatorColumns());
    }

    public function setAggregators(array $aggregators): void
    {
        $this->isChanged('aggregators', $aggregators);

        $this->aggregators = $aggregators;
    }

    public function setSettings(array $settings): void
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
    public function setIsScheduled($isScheduled): void
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
    public function setToAddress($toAddress): void
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
    public function setScheduleUnit($scheduleUnit): void
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
    public function setScheduleDay($scheduleDay): void
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
    public function setScheduleMonthFrequency($scheduleMonthFrequency): void
    {
        $this->scheduleMonthFrequency = $scheduleMonthFrequency;
    }

    public function setAsNotScheduled(): void
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

    public function ensureIsDailyScheduled(): void
    {
        $this->setIsScheduled(true);
        $this->setScheduleUnit(SchedulerEnum::UNIT_DAILY);
        $this->setScheduleDay(null);
        $this->setScheduleMonthFrequency(null);
    }

    /**
     * @throws ScheduleNotValidException
     */
    public function ensureIsMonthlyScheduled(): void
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
    public function ensureIsWeeklyScheduled(): void
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

    public function isScheduledDaily(): bool
    {
        return SchedulerEnum::UNIT_DAILY === $this->getScheduleUnit();
    }

    public function isScheduledWeekly(): bool
    {
        return SchedulerEnum::UNIT_WEEKLY === $this->getScheduleUnit();
    }

    public function isScheduledMonthly(): bool
    {
        return SchedulerEnum::UNIT_MONTHLY === $this->getScheduleUnit();
    }

    public function isScheduledWeekDays(): bool
    {
        return SchedulerEnum::DAY_WEEK_DAYS === $this->getScheduleDay();
    }
}
