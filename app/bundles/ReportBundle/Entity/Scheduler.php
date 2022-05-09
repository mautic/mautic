<?php

namespace Mautic\ReportBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;

class Scheduler
{
    public const TABLE_NAME = 'reports_schedulers';

    /**
     * @var int
     */
    private $id;

    /**
     * @var Report
     */
    private $report;

    /**
     * @var array<mixed>
     */
    private array $data = [];

    /**
     * @var \DateTimeInterface
     */
    private $scheduleDate;

    public static function loadMetadata(ORM\ClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable(self::TABLE_NAME)
            ->setCustomRepositoryClass(SchedulerRepository::class);

        $builder->addId();

        $builder->createManyToOne('report', Report::class)
            ->addJoinColumn('report_id', 'id', false, false, 'CASCADE')
            ->build();

        $builder->createField('scheduleDate', Types::DATETIME_MUTABLE)
            ->columnName('schedule_date')
            ->nullable(false)
            ->build();

        $builder->createField('data', Types::JSON)
            ->columnName('data')
            ->build();
    }

    public function __construct(
        private Report $report,
        private \DateTimeInterface $scheduleDate,
    ) {
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

    /**
     * @return Report
     */
    public function getReport()
    {
        return $this->report;
    }

    /**
     * @return \DateTimeInterface
     */
    public function getScheduleDate()
    {
        return $this->scheduleDate;
    }

    /**
     * @param array<mixed> $data
     */
    public function setData(array $data): Scheduler
    {
        $this->data = $data;

        return $this;
    }

    /**
     * @return array<mixed>
     */
    public function getData(): array
    {
        return $this->data;
    }
}
