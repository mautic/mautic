<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\DynamicContentBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Mautic\ApiBundle\Serializer\Driver\ApiMetadataDriver;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Mautic\LeadBundle\Entity\Lead;

/**
 * Class Stat.
 */
class Stat
{
    /**
     * @var int
     */
    private $id;

    /**
     * @var DynamicContent
     */
    private $dynamicContent;

    /**
     * @var \Mautic\LeadBundle\Entity\Lead
     */
    private $lead;

    /**
     * @var \DateTime
     */
    private $dateSent;

    /**
     * @var int
     */
    private $sentCount;

    /**
     * @var int
     */
    private $lastSent;

    /**
     * @var array
     */
    private $sentDetails = [];

    /**
     * @var string
     */
    private $source;

    /**
     * @var int
     */
    private $sourceId;

    /**
     * @var array
     */
    private $tokens = [];

    /**
     * @param ORM\ClassMetadata $metadata
     */
    public static function loadMetadata(ORM\ClassMetadata $metadata)
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable('dynamic_content_stats')
            ->setCustomRepositoryClass('Mautic\DynamicContentBundle\Entity\StatRepository')
            ->addIndex(['dynamic_content_id', 'lead_id'], 'stat_dynamic_content_search')
            ->addIndex(['source', 'source_id'], 'stat_dynamic_content_source_search');

        $builder->addId();

        $builder->createManyToOne('dynamicContent', 'DynamicContent')
            ->inversedBy('stats')
            ->addJoinColumn('dynamic_content_id', 'id', true, false, 'SET NULL')
            ->build();

        $builder->addLead(true, 'SET NULL');

        $builder->createField('dateSent', 'datetime')
            ->columnName('date_sent')
            ->build();

        $builder->createField('source', 'string')
            ->nullable()
            ->build();

        $builder->createField('sourceId', 'integer')
            ->columnName('source_id')
            ->nullable()
            ->build();

        $builder->createField('tokens', 'array')
            ->nullable()
            ->build();

        $builder->addNullableField('sentCount', 'integer', 'sent_count');

        $builder->addNullableField('lastSent', 'datetime', 'last_sent');

        $builder->addNullableField('sentDetails', 'array', 'sent_details');
    }

    /**
     * Prepares the metadata for API usage.
     *
     * @param $metadata
     */
    public static function loadApiMetadata(ApiMetadataDriver $metadata)
    {
        $metadata->setGroupPrefix('stat')
            ->addProperties(
                [
                    'id',
                    'dateSent',
                    'source',
                    'sentCount',
                    'lastSent',
                    'sourceId',
                    'lead',
                    'dynamicContent',
                ]
            )
            ->build();
    }

    /**
     * @param $details
     */
    public function addSentDetails($details)
    {
        $this->sentDetails[] = $details;

        ++$this->sentCount;
    }

    /**
     * Up the sent count.
     *
     * @return Stat
     */
    public function upSentCount()
    {
        $count           = (int) $this->sentCount + 1;
        $this->sentCount = $count;

        return $this;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return DynamicContent
     */
    public function getDynamicContent()
    {
        return $this->dynamicContent;
    }

    /**
     * @param DynamicContent $dynamicContent
     */
    public function setDynamicContent(DynamicContent $dynamicContent)
    {
        $this->dynamicContent = $dynamicContent;
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
    public function setLead($lead)
    {
        $this->lead = $lead;
    }

    /**
     * @return \DateTime
     */
    public function getDateSent()
    {
        return $this->dateSent;
    }

    /**
     * @param \DateTime $dateSent
     */
    public function setDateSent($dateSent)
    {
        $this->dateSent = $dateSent;
    }

    /**
     * @return int
     */
    public function getSentCount()
    {
        return $this->sentCount;
    }

    /**
     * @param int $sentCount
     */
    public function setSentCount($sentCount)
    {
        $this->sentCount = $sentCount;
    }

    /**
     * @return int
     */
    public function getLastSent()
    {
        return $this->lastSent;
    }

    /**
     * @param int $lastSent
     */
    public function setLastSent($lastSent)
    {
        $this->lastSent = $lastSent;
    }

    /**
     * @return array
     */
    public function getSentDetails()
    {
        return $this->sentDetails;
    }

    /**
     * @param array $sentDetails
     */
    public function setSentDetails($sentDetails)
    {
        $this->sentDetails = $sentDetails;
    }

    /**
     * @return string
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * @param string $source
     */
    public function setSource($source)
    {
        $this->source = $source;
    }

    /**
     * @return int
     */
    public function getSourceId()
    {
        return $this->sourceId;
    }

    /**
     * @param int $sourceId
     */
    public function setSourceId($sourceId)
    {
        $this->sourceId = $sourceId;
    }

    /**
     * @return array
     */
    public function getTokens()
    {
        return $this->tokens;
    }

    /**
     * @param array $tokens
     */
    public function setTokens($tokens)
    {
        $this->tokens = $tokens;
    }
}
