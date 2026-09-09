<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Mautic\ApiBundle\Serializer\Driver\ApiMetadataDriver;

#[ORM\Entity(repositoryClass: FailedLeadEventLogRepository::class)]
#[ORM\Table(name: 'campaign_lead_event_failed_log')]
#[ORM\Index(columns: ['date_added'], name: 'campaign_event_failed_date')]
#[ORM\ChangeTrackingPolicy('DEFERRED_EXPLICIT')]
class FailedLeadEventLog
{
    #[ORM\Id]
    #[ORM\OneToOne(targetEntity: 'LeadEventLog', inversedBy: 'failedLog')]
    #[ORM\JoinColumn(name: 'log_id', nullable: false, onDelete: 'CASCADE')]
    private ?\Mautic\CampaignBundle\Entity\LeadEventLog $log = null;

    #[ORM\Column(name: 'date_added', type: 'datetime')]
    private ?\DateTime $dateAdded = null;

    /**
     * @var string|null
     */
    #[ORM\Column(type: 'text', nullable: true)]
    private $reason;

    /**
     * Prepares the metadata for API usage.
     */
    public static function loadApiMetadata(ApiMetadataDriver $metadata): void
    {
        $metadata->setGroupPrefix('campaignEventFailedLog')
            ->addProperties(
                [
                    'dateAdded',
                    'reason',
                ]
            )
            ->build();
    }

    /**
     * @return LeadEventLog
     */
    public function getLog()
    {
        return $this->log;
    }

    public function setLog(?LeadEventLog $log = null): static
    {
        $this->log = $log;

        if ($log) {
            $log->setFailedLog($this);
        }

        return $this;
    }

    /**
     * @return \DateTimeInterface
     */
    public function getDateAdded()
    {
        return $this->dateAdded;
    }

    public function setDateAdded(?\DateTime $dateAdded = null): static
    {
        $dateAdded ??= new \DateTime();

        $this->dateAdded = $dateAdded;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getReason()
    {
        return $this->reason;
    }

    /**
     * @param string $reason
     */
    public function setReason($reason): static
    {
        $this->reason = $reason;

        return $this;
    }
}
