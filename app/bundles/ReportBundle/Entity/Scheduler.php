<?php

declare(strict_types=1);

namespace Mautic\ReportBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;

#[ORM\Entity(repositoryClass: SchedulerRepository::class)]
#[ORM\Table(name: 'reports_schedulers')]
#[ORM\ChangeTrackingPolicy('DEFERRED_EXPLICIT')]
class Scheduler
{
    /**
     * @var int
     */
    private $id;

    public static function loadMetadata(ORM\ClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->addId();
    }

    public function __construct(
        #[ORM\ManyToOne(targetEntity: Report::class)]
        #[ORM\JoinColumn(name: 'report_id', nullable: false, onDelete: 'CASCADE')]
        private readonly Report $report,
        #[ORM\Column(name: 'schedule_date', type: Types::DATETIME_MUTABLE)]
        private readonly \DateTimeInterface $scheduleDate,
    ) {
    }

    /**
     * @return int|null
     */
    public function getId()
    {
        return $this->id;
    }

    public function getReport(): Report
    {
        return $this->report;
    }

    public function getScheduleDate(): \DateTimeInterface
    {
        return $this->scheduleDate;
    }
}
