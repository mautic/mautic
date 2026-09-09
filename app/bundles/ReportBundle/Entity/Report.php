<?php

namespace Mautic\ReportBundle\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Mautic\ApiBundle\Serializer\Driver\ApiMetadataDriver;
use Mautic\CoreBundle\Entity\FormEntity;
use Mautic\CoreBundle\Entity\UuidInterface;
use Mautic\CoreBundle\Entity\UuidTrait;
use Mautic\EmailBundle\Validator as EmailAssert;
use Mautic\ReportBundle\Scheduler\Enum\SchedulerEnum;
use Mautic\ReportBundle\Scheduler\Exception\ScheduleNotValidException;
use Mautic\ReportBundle\Scheduler\SchedulerInterface;
use Mautic\ReportBundle\Scheduler\Validator as ReportAssert;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints\NotBlank;

#[ApiResource(
    operations: [
        new GetCollection(security: "is_granted('report:reports:viewown')"),
        new Post(security: "is_granted('report:reports:create')"),
        new Get(security: "is_granted('report:reports:viewown', object)"),
        new Put(security: "is_granted('report:reports:editown', object)"),
        new Patch(security: "is_granted('report:reports:editother', object)"),
        new Delete(security: "is_granted('report:reports:deleteown', object)"),
    ],
    normalizationContext: [
        'groups'                  => ['report:read'],
        'swagger_definition_name' => 'Read',
    ],
    denormalizationContext: [
        'groups'                  => ['report:write'],
        'swagger_definition_name' => 'Write',
    ]
)]
#[ReportAssert\ScheduleIsValid]
#[ORM\Entity(repositoryClass: ReportRepository::class)]
#[ORM\Table(name: 'reports')]
#[ORM\ChangeTrackingPolicy('DEFERRED_EXPLICIT')]
class Report extends FormEntity implements SchedulerInterface, UuidInterface
{
    use UuidTrait;

    #[Groups(['report:read'])]
    #[ORM\Id]
    #[ORM\Column(type: 'integer', options: ['unsigned' => true])]
    #[ORM\GeneratedValue]
    private ?int $id = null;

    /**
     * @var string
     */
    #[Groups(['report:read', 'report:write'])]
    #[NotBlank(message: 'mautic.core.name.required')]
    #[ORM\Column(type: 'string', length: 191)]
    private $name;

    /**
     * @var string|null
     */
    #[Groups(['report:read', 'report:write'])]
    #[ORM\Column(type: 'text', nullable: true)]
    private $description;

    /**
     * @var bool
     */
    #[Groups(['report:read', 'report:write'])]
    #[ORM\Column(name: '`system`', type: Types::BOOLEAN)]
    private $system = false;

    /**
     * @var string
     */
    #[Groups(['report:read', 'report:write'])]
    #[ORM\Column(type: Types::STRING, length: 191)]
    private $source;

    /**
     * @var array
     */
    #[Groups(['report:read', 'report:write'])]
    #[ORM\Column(type: Types::ARRAY, nullable: true)]
    private $columns = [];

    /**
     * @var array
     */
    #[Groups(['report:read', 'report:write'])]
    #[ORM\Column(type: Types::ARRAY, nullable: true)]
    private $filters = [];

    #[Groups(['report:read', 'report:write'])]
    #[ORM\Column(name: 'table_order', type: Types::ARRAY, nullable: true)]
    private array $tableOrder = [];

    #[Groups(['report:read', 'report:write'])]
    #[ORM\Column(type: Types::ARRAY, nullable: true)]
    private array $graphs = [];

    #[Groups(['report:read', 'report:write'])]
    #[ORM\Column(name: 'group_by', type: Types::ARRAY, nullable: true)]
    private array $groupBy = [];

    #[Groups(['report:read', 'report:write'])]
    #[ORM\Column(type: Types::ARRAY, nullable: true)]
    private array $aggregators = [];

    #[Groups(['report:read', 'report:write'])]
    #[ORM\Column(type: Types::JSON, nullable: true)]
    private array $settings = [];

    /**
     * @var bool
     *
     * @ApiProperty(readable=true)
     */
    #[Groups(['report:read', 'report:write'])]
    #[ORM\Column(name: 'is_scheduled', type: Types::BOOLEAN)]
    private $isScheduled = false;

    /**
     * @var string|null
     */
    #[Groups(['report:read', 'report:write'])]
    #[EmailAssert\MultipleEmailsValid]
    #[ORM\Column(name: 'to_address', type: Types::STRING, length: 191, nullable: true)]
    private $toAddress;

    /**
     * @var string|null
     */
    #[Groups(['report:read', 'report:write'])]
    #[ORM\Column(name: 'schedule_unit', type: Types::STRING, length: 191, nullable: true)]
    private $scheduleUnit;

    /**
     * @var string|null
     */
    #[Groups(['report:read', 'report:write'])]
    #[ORM\Column(name: 'schedule_day', type: Types::STRING, length: 191, nullable: true)]
    private $scheduleDay;

    /**
     * @var string|null
     */
    #[Groups(['report:read', 'report:write'])]
    #[ORM\Column(name: 'schedule_month_frequency', type: Types::STRING, length: 191, nullable: true)]
    private $scheduleMonthFrequency;

    private bool $hasScheduleChanged = false;

    public function __clone()
    {
        $this->id = null;

        parent::__clone();
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
     * @return int|null
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
     */
    public function setName($name): static
    {
        $this->isChanged('name', $name);
        $this->name = $name;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $system
     */
    public function setSystem($system): static
    {
        $this->isChanged('system', $system);
        $this->system = $system;

        return $this;
    }

    /**
     * @return bool
     */
    public function getSystem()
    {
        return $this->system;
    }

    /**
     * @param string $source
     */
    public function setSource($source): static
    {
        $this->isChanged('source', $source);
        $this->source = $source;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * @param mixed[] $columns
     */
    public function setColumns($columns): static
    {
        $this->isChanged('columns', $columns);
        $this->columns = $columns;

        return $this;
    }

    /**
     * @return array<array-key, mixed>
     */
    public function getColumns()
    {
        return $this->columns;
    }

    /**
     * @param mixed[] $filters
     */
    public function setFilters($filters): static
    {
        $this->isChanged('filters', $filters);
        $this->filters = $filters;

        return $this;
    }

    /**
     * @return array<array-key, mixed>
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
        foreach ($this->filters as $field) {
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
        foreach ($this->filters as $field) {
            if ($column === $field['column']) {
                $values[] = $field['value'];
            }
        }

        if ([] === $values) {
            throw new \UnexpectedValueException("Column {$column} doesn't have any filter.");
        }

        return $values;
    }

    /**
     * @return string|null
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
     * @return array<array-key, mixed>
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
     * @return array<array-key, mixed>
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
     * @return array<array-key, mixed>
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
     * @return array<array-key, mixed>
     */
    public function getAggregators()
    {
        return $this->aggregators;
    }

    public function getAggregatorColumns(): array
    {
        return array_map(fn (array $aggregator): mixed => $aggregator['column'], $this->aggregators);
    }

    public function getOrderColumns(): array
    {
        return array_map(fn (array $order): mixed => $order['column'], $this->tableOrder);
    }

    public function getSelectAndAggregatorAndOrderAndGroupByColumns(): array
    {
        return array_merge($this->getSelectAndAggregatorColumns(), $this->getOrderColumns(), $this->groupBy);
    }

    public function getSelectAndAggregatorColumns(): array
    {
        return array_merge($this->columns, $this->getAggregatorColumns());
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
     * @return array<array-key, mixed>|null
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
            !in_array($this->scheduleMonthFrequency, SchedulerEnum::getMonthFrequencyForSelect())
            || !in_array($this->scheduleDay, SchedulerEnum::getDayEnumForSelect())
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
        if (!in_array($this->scheduleDay, SchedulerEnum::getDayEnumForSelect())) {
            throw new ScheduleNotValidException();
        }
        $this->setIsScheduled(true);
        $this->setScheduleUnit(SchedulerEnum::UNIT_WEEKLY);
        $this->setScheduleMonthFrequency(null);
    }

    public function isScheduledNow(): bool
    {
        return SchedulerEnum::UNIT_NOW === $this->scheduleUnit;
    }

    public function isScheduledDaily(): bool
    {
        return SchedulerEnum::UNIT_DAILY === $this->scheduleUnit;
    }

    public function isScheduledWeekly(): bool
    {
        return SchedulerEnum::UNIT_WEEKLY === $this->scheduleUnit;
    }

    public function isScheduledMonthly(): bool
    {
        return SchedulerEnum::UNIT_MONTHLY === $this->scheduleUnit;
    }

    public function isScheduledWeekDays(): bool
    {
        return SchedulerEnum::DAY_WEEK_DAYS === $this->scheduleDay;
    }

    public function getHasScheduleChanged(): bool
    {
        return $this->hasScheduleChanged;
    }

    public function setHasScheduleChanged(bool $hasScheduleChanged): void
    {
        $this->hasScheduleChanged = $hasScheduleChanged;
    }

    /**
     * @return array<string, string|null>
     */
    public function getSchedule(): array
    {
        return ['schedule_unit' => $this->scheduleUnit, 'schedule_day' => $this->scheduleDay, 'schedule_month_frequency' => $this->scheduleMonthFrequency];
    }
}
