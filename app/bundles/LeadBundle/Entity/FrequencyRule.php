<?php

namespace Mautic\LeadBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Mautic\ApiBundle\Serializer\Driver\ApiMetadataDriver;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Mautic\CoreBundle\Entity\CommonEntity;

class FrequencyRule extends CommonEntity
{
    public const TIME_DAY   = 'DAY';

    public const TIME_WEEK  = 'WEEK';

    public const TIME_MONTH = 'MONTH';

    /**
     * @var int
     */
    private $id;

    /**
     * @var Lead
     */
    private $lead;

    /**
     * @var \DateTimeInterface
     */
    private $dateAdded;

    /**
     * @var int|null
     */
    private $frequencyNumber;

    /**
     * @var string|null
     */
    private $frequencyTime;

    /**
     * @var string
     */
    private $channel;

    private bool $preferredChannel = false;

    /**
     * @var \DateTimeInterface
     */
    private $pauseFromDate;

    /**
     * @var \DateTimeInterface
     */
    private $pauseToDate;

    public static function loadMetadata(ORM\ClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable('lead_frequencyrules')
            ->setCustomRepositoryClass(FrequencyRuleRepository::class)
            ->addIndex(['channel'], 'channel_frequency')
            ->addIndex(['lead_id', 'date_added'], 'idx_frequency_date_added');

        $builder->addId();

        $builder->addLead(false, 'CASCADE', false, 'frequencyRules');

        $builder->addDateAdded();

        $builder->addNamedField('frequencyNumber', 'smallint', 'frequency_number', true);

        $builder->createField('frequencyTime', 'string')
            ->columnName('frequency_time')
            ->nullable()
            ->length(25)
            ->build();

        $builder->createField('channel', 'string')
            ->build();

        $builder->createField('preferredChannel', 'boolean')
            ->columnName('preferred_channel')
            ->build();

        $builder->createField('pauseFromDate', 'datetime')
            ->columnName('pause_from_date')
            ->nullable()
            ->build();

        $builder->createField('pauseToDate', 'datetime')
            ->columnName('pause_to_date')
            ->nullable()
            ->build();
    }

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

    /**
     * @return \DateTimeInterface
     */
    public function getPauseFromDate()
    {
        return $this->pauseFromDate;
    }

    public function setPauseFromDate(?\DateTime $pauseFromDate = null): static
    {
        $this->isChanged('pauseFromDate', $pauseFromDate);

        $this->pauseFromDate = $pauseFromDate;

        return $this;
    }

    /**
     * @return \DateTimeInterface
     */
    public function getPauseToDate()
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
