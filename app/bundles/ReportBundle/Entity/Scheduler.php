<?php

declare(strict_types=1);

namespace Mautic\ReportBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SchedulerRepository::class)]
#[ORM\Table(name: 'reports_schedulers')]
#[ORM\ChangeTrackingPolicy('DEFERRED_EXPLICIT')]
class Scheduler
{
    /**
     * @var int
     */
    #[ORM\Id]
    #[ORM\Column(type: 'integer', options: ['unsigned' => true])]
    #[ORM\GeneratedValue]
    private $id;

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
