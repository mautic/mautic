<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Mautic\ApiBundle\Serializer\Driver\ApiMetadataDriver;
use Mautic\CoreBundle\Entity\CommonEntity;

#[ORM\Entity(repositoryClass: FrequencyRuleRepository::class)]
#[ORM\Table(name: 'lead_frequencyrules')]
#[ORM\Index(columns: ['channel'], name: 'channel_frequency')]
#[ORM\Index(columns: ['lead_id', 'date_added'], name: 'idx_frequency_date_added')]
#[ORM\ChangeTrackingPolicy('DEFERRED_EXPLICIT')]
class FrequencyRule extends CommonEntity
{
    public const TIME_DAY   = 'DAY';

    public const TIME_WEEK  = 'WEEK';

    public const TIME_MONTH = 'MONTH';

    /**
     * @var int
     */
    #[ORM\Id]
    #[ORM\Column(type: 'integer', options: ['unsigned' => true])]
    #[ORM\GeneratedValue]
    private $id;

    /**
     * @var Lead
     */
    #[ORM\ManyToOne(targetEntity: \Mautic\LeadBundle\Entity\Lead::class, inversedBy: 'frequencyRules')]
    #[ORM\JoinColumn(name: 'lead_id', nullable: false, onDelete: 'CASCADE')]
    private $lead;

    /**
     * @var \DateTimeInterface
     */
    #[ORM\Column(name: 'date_added', type: 'datetime')]
    private $dateAdded;

    /**
     * @var int|null
     */
    #[ORM\Column(name: 'frequency_number', type: 'smallint', nullable: true)]
    private $frequencyNumber;

    /**
     * @var string|null
     */
    #[ORM\Column(name: 'frequency_time', type: 'string', length: 25, nullable: true)]
    private $frequencyTime;

    /**
     * @var string
     */
    #[ORM\Column(type: 'string', length: 191)]
    private $channel;

    #[ORM\Column(name: 'preferred_channel', type: 'boolean')]
    private bool $preferredChannel = false;

    #[ORM\Column(name: 'pause_from_date', type: 'datetime', nullable: true)]
    private ?\DateTime $pauseFromDate = null;

    #[ORM\Column(name: 'pause_to_date', type: 'datetime', nullable: true)]
    private ?\DateTime $pauseToDate = null;

    /**
     * Prepares the metadata for API usage.
     */
    public static function loadApiMetadata(ApiMetadataDriver $metadata): void
    {
        $metadata->setGroupPrefix('frequencyRules')
                 ->addListProperties(
                     [
                         'channel',
                         'frequencyNumber',
                         'frequencyTime',
                         'preferredChannel',
                         'pauseFromDate',
                         'pauseToDate',
                     ]
                 )
                 ->addProperties(
                     [
                         'lead',
                         'dateAdded',
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

    /**
     * @return Lead
     */
    public function getLead()
    {
        return $this->lead;
    }

    /**
     * @param Lead $lead
     */
    public function setLead($lead): static
    {
        $this->lead = $lead;

        return $this;
    }

    /**
     * @return \DateTimeInterface|null
     */
    public function getDateAdded()
    {
        return $this->dateAdded;
    }

    /**
     * @param \DateTimeInterface $dateAdded
     */
    public function setDateAdded($dateAdded): static
    {
        $this->isChanged('dateAdded', $dateAdded);

        $this->dateAdded = $dateAdded;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getFrequencyNumber()
    {
        return $this->frequencyNumber;
    }

    /**
     * @param int|null $frequencyNumber
     */
    public function setFrequencyNumber($frequencyNumber): static
    {
        $this->isChanged('frequencyNumber', $frequencyNumber);

        $this->frequencyNumber = $frequencyNumber;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getFrequencyTime()
    {
        return $this->frequencyTime;
    }

    /**
     * @param string|null $frequencyTime
     */
    public function setFrequencyTime($frequencyTime): static
    {
        $this->isChanged('frequencyTime', $frequencyTime);

        $this->frequencyTime = $frequencyTime;

        return $this;
    }

    /**
     * @return string
     */
    public function getChannel()
    {
        return $this->channel;
    }

    /**
     * @param string $channel
     */
    public function setChannel($channel): static
    {
        $this->isChanged('channel', $channel);

        $this->channel = $channel;

        return $this;
    }

    public function isPreferredChannel(): bool
    {
        return $this->preferredChannel;
    }

    public function getPreferredChannel(): bool
    {
        return $this->preferredChannel;
    }

    public function setPreferredChannel(bool $preferredChannel): static
    {
        $this->isChanged('preferredChannel', $preferredChannel);

        $this->preferredChannel = $preferredChannel;

        return $this;
    }

    public function getPauseFromDate(): ?\DateTime
    {
        return $this->pauseFromDate;
    }

    public function setPauseFromDate(?\DateTime $pauseFromDate = null): static
    {
        $this->isChanged('pauseFromDate', $pauseFromDate);

        $this->pauseFromDate = $pauseFromDate;

        return $this;
    }

    public function getPauseToDate(): ?\DateTime
    {
        return $this->pauseToDate;
    }

    public function setPauseToDate(?\DateTime $pauseToDate = null): static
    {
        $this->isChanged('pauseToDate', $pauseToDate);

        $this->pauseToDate = $pauseToDate;

        return $this;
    }
}
