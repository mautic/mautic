<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Mautic\ApiBundle\Serializer\Driver\ApiMetadataDriver;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;

#[ORM\Entity(repositoryClass: FailedLeadEventLogRepository::class)]
#[ORM\Table(name: 'campaign_lead_event_failed_log')]
#[ORM\Index(name: 'campaign_event_failed_date', columns: ['date_added'])]
#[ORM\ChangeTrackingPolicy('DEFERRED_EXPLICIT')]
class FailedLeadEventLog
{
    /**
     * @var LeadEventLog
     */
    #[ORM\Id]
    #[ORM\OneToOne(targetEntity: LeadEventLog::class, inversedBy: 'failedLog')]
    #[ORM\JoinColumn(name: 'log_id', nullable: false, onDelete: 'CASCADE')]
    private $log;

    /**
     * @var \DateTimeInterface
     */
    private $dateAdded;

    /**
     * @var string|null
     */
    private $reason;

    public static function loadMetadata(ORM\ClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->addDateAdded();

        $builder->addNullableField('reason', 'text');
    }

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
