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

    /**
     * @var bool
     */
    private $preferredChannel = 0;

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
            ->setCustomRepositoryClass(\Mautic\LeadBundle\Entity\FrequencyRuleRepository::class)
            ->addIndex(['channel'], 'channel_frequency');

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
     *
     * @return FrequencyRule
     */
    public function setLead($lead)
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
     *
     * @return FrequencyRule
     */
    public function setDateAdded($dateAdded)
    {
        $this->isChanged('dateAdded', $dateAdded);

        $this->dateAdded = $dateAdded;

        return $this;
    }

    /**
     * @return int
     */
    public function getFrequencyNumber()
    {
        return $this->frequencyNumber;
    }

    /**
     * @param int|null $frequencyNumber
     *
     * @return FrequencyRule
     */
    public function setFrequencyNumber($frequencyNumber)
    {
        $this->isChanged('frequencyNumber', $frequencyNumber);

        $this->frequencyNumber = $frequencyNumber;

        return $this;
    }

    /**
     * @return string
     */
    public function getFrequencyTime()
    {
        return $this->frequencyTime;
    }

    /**
     * @param string|null $frequencyTime
     *
     * @return FrequencyRule
     */
    public function setFrequencyTime($frequencyTime)
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
     *
     * @return FrequencyRule
     */
    public function setChannel($channel)
    {
        $this->isChanged('channel', $channel);

        $this->channel = $channel;

        return $this;
    }

    /**
     * @return bool
     */
    public function isPreferredChannel()
    {
        return $this->preferredChannel;
    }

    /**
     * @return bool
     */
    public function getPreferredChannel()
    {
        return $this->preferredChannel;
    }

    /**
     * @param bool $preferredChannel
     *
     * @return FrequencyRule
     */
    public function setPreferredChannel($preferredChannel)
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

    /**
     * @return FrequencyRule
     */
    public function setPauseFromDate(\DateTime $pauseFromDate = null)
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

    /**
     * @return FrequencyRule
     */
    public function setPauseToDate(\DateTime $pauseToDate = null)
    {
        $this->isChanged('pauseToDate', $pauseToDate);

        $this->pauseToDate = $pauseToDate;

        return $this;
    }
}
