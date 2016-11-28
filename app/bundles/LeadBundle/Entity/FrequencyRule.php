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
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;

/**
 * Class FrequencyRule.
 */
class FrequencyRule
{
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
     * @var date
     */
    private $pauseFromDate;

    /**
     * @var date
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
     * @return string
     */
    public function getChannel()
    {
        return $this->channel;
    }

    /**
     * @param string $channel
     */
    public function setChannel($channel)
    {
        $this->channel = $channel;
    }

    /**
     * @return mixed
     */
    public function getChannelId()
    {
        return $this->channelId;
    }

    /**
     * @param mixed $channelId
     *
     * @return DoNotContact
     */
    public function setChannelId($channelId)
    {
        $this->channelId = $channelId;
    }

    /**
     * @return mixed
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
    public function setLead(Lead $lead)
    {
        $this->lead = $lead;
    }

    /**
     * @return \DateTime
     */
    public function getDateAdded()
    {
        return $this->dateAdded;
    }

    /**
     * @param mixed $dateAdded
     */
    public function setDateAdded($dateAdded)
    {
        $this->dateAdded = $dateAdded;
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
     */
    public function setFrequencyNumber($frequencyNumber)
    {
        $this->frequencyNumber = $frequencyNumber;
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
     */
    public function setFrequencyTime($frequencyTime)
    {
        $this->frequencyTime = $frequencyTime;
    }

    /**
     * @param $preferredChannel
     */
    public function setPreferredChannel($preferredChannel)
    {
        $this->preferredChannel = $preferredChannel;
    }

    /**
     * @return bool
     */
    public function getPreferredChannel()
    {
        return $this->preferredChannel;
    }

    /**
     * @param $pauseFromDate
     */
    public function setPauseFromDate($pauseFromDate)
    {
        $this->pauseFromDate = $pauseFromDate;
    }

    /**
     * @return datetime
     */
    public function getPauseFromDate()
    {
        return $this->pauseFromDate;
    }

    /**
     * @param $pauseToDate
     */
    public function setPauseToDate($pauseToDate)
    {
        $this->pauseToDate = $pauseToDate;
    }

    /**
     * @return datetime
     */
    public function getPauseToDate()
    {
        return $this->pauseToDate;
    }
}
