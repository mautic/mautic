<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Mautic\ApiBundle\Serializer\Driver\ApiMetadataDriver;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Mautic\CoreBundle\Entity\CommonEntity;

/**
 * Class FrequencyRule.
 */
class FrequencyRule extends CommonEntity
{
    const TIME_DAY   = 'DAY';
    const TIME_WEEK  = 'WEEK';
    const TIME_MONTH = 'MONTH';

    /**
     * @var int
     */
    private $id;

    /**
     * @var Lead
     */
    private $lead;

    /**
     * @var \DateTime
     */
    private $dateAdded;

    /**
     * @var int
     */
    private $frequencyNumber;

    /**
     * @var string
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
     * @var \DateTime
     */
    private $pauseFromDate;

    /**
     * @var \DateTime
     */
    private $pauseToDate;

    /**
     * @param ORM\ClassMetadata $metadata
     */
    public static function loadMetadata(ORM\ClassMetadata $metadata)
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable('lead_frequencyrules')
            ->setCustomRepositoryClass('Mautic\LeadBundle\Entity\FrequencyRuleRepository')
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
     *
     * @param $metadata
     */
    public static function loadApiMetadata(ApiMetadataDriver $metadata)
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
     * @return \DateTime
     */
    public function getDateAdded()
    {
        return $this->dateAdded;
    }

    /**
     * @param \DateTime $dateAdded
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
     * @param int $frequencyNumber
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
     * @param string $frequencyTime
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
     * @return \DateTime
     */
    public function getPauseFromDate()
    {
        return $this->pauseFromDate;
    }

    /**
     * @param \DateTime $pauseFromDate
     *
     * @return FrequencyRule
     */
    public function setPauseFromDate(\DateTime $pauseFromDate = null)
    {
        $this->isChanged('pauseFromDate', $pauseFromDate);

        $this->pauseFromDate = $pauseFromDate;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getPauseToDate()
    {
        return $this->pauseToDate;
    }

    /**
     * @param \DateTime $pauseToDate
     *
     * @return FrequencyRule
     */
    public function setPauseToDate(\DateTime $pauseToDate = null)
    {
        $this->isChanged('pauseToDate', $pauseToDate);

        $this->pauseToDate = $pauseToDate;

        return $this;
    }
}
