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

        $builder->addNamedField('frequencyNumber', 'smallint', 'frequency_number');

        $builder->createField('frequencyTime', 'string')
            ->columnName('frequency_time')
            ->length(25)
            ->build();

        $builder->createField('channel', 'string')
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
}
