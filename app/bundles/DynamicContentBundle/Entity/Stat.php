<?php

declare(strict_types=1);

namespace Mautic\DynamicContentBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Mautic\ApiBundle\Serializer\Driver\ApiMetadataDriver;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Mautic\LeadBundle\Entity\Lead;

#[ORM\Entity(repositoryClass: StatRepository::class)]
#[ORM\Table(name: 'dynamic_content_stats')]
#[ORM\Index(columns: ['dynamic_content_id', 'lead_id'], name: 'stat_dynamic_content_search')]
#[ORM\Index(columns: ['source', 'source_id'], name: 'stat_dynamic_content_source_search')]
#[ORM\Index(columns: ['date_sent'], name: 'stat_dynamic_content_date_sent')]
#[ORM\ChangeTrackingPolicy('DEFERRED_EXPLICIT')]
class Stat
{
    /**
     * @var string
     */
    private $id;

    /**
     * @var DynamicContent|null
     */
    #[ORM\ManyToOne(targetEntity: DynamicContent::class, inversedBy: 'stats')]
    #[ORM\JoinColumn(name: 'dynamic_content_id', onDelete: 'SET NULL')]
    private $dynamicContent;

    /**
     * @var Lead|null
     */
    private $lead;

    /**
     * @var \DateTimeInterface
     */
    #[ORM\Column(name: 'date_sent', type: 'datetime')]
    private $dateSent;

    /**
     * @var int|null
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
     * @var string|null
     */
    #[ORM\Column(type: 'string', length: 191, nullable: true)]
    private $source;

    /**
     * @var int|null
     */
    #[ORM\Column(name: 'source_id', type: 'integer', nullable: true)]
    private $sourceId;

    /**
     * @var array
     */
    #[ORM\Column(type: 'array', nullable: true)]
    private $tokens = [];

    public static function loadMetadata(ORM\ClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->addBigIntIdField();

        $builder->addLead(true, 'SET NULL');

        $builder->addNullableField('sentCount', 'integer', 'sent_count');

        $builder->addNullableField('lastSent', 'datetime', 'last_sent');

        $builder->addNullableField('sentDetails', 'array', 'sent_details');
    }

    /**
     * Prepares the metadata for API usage.
     */
    public static function loadApiMetadata(ApiMetadataDriver $metadata): void
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

    public function addSentDetails($details): void
    {
        $this->sentDetails[] = $details;

        ++$this->sentCount;
    }

    public function upSentCount(): static
    {
        $count           = (int) $this->sentCount + 1;
        $this->sentCount = $count;

        return $this;
    }

    public function getId(): int
    {
        return (int) $this->id;
    }

    /**
     * @param int $id
     */
    public function setId($id): void
    {
        $this->id = (string) $id;
    }

    /**
     * @return DynamicContent|null
     */
    public function getDynamicContent()
    {
        return $this->dynamicContent;
    }

    public function setDynamicContent(DynamicContent $dynamicContent): void
    {
        $this->dynamicContent = $dynamicContent;
    }

    /**
     * @return Lead|null
     */
    public function getLead()
    {
        return $this->lead;
    }

    /**
     * @param Lead $lead
     */
    public function setLead($lead): void
    {
        $this->lead = $lead;
    }

    /**
     * @return \DateTimeInterface|null
     */
    public function getDateSent()
    {
        return $this->dateSent;
    }

    /**
     * @param \DateTime $dateSent
     */
    public function setDateSent($dateSent): void
    {
        $this->dateSent = $dateSent;
    }

    /**
     * @return int|null
     */
    public function getSentCount()
    {
        return $this->sentCount;
    }

    /**
     * @param int $sentCount
     */
    public function setSentCount($sentCount): void
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
    public function setLastSent($lastSent): void
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
    public function setSentDetails($sentDetails): void
    {
        $this->sentDetails = $sentDetails;
    }

    /**
     * @return string|null
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * @param string $source
     */
    public function setSource($source): void
    {
        $this->source = $source;
    }

    /**
     * @return int|null
     */
    public function getSourceId()
    {
        return $this->sourceId;
    }

    /**
     * @param int $sourceId
     */
    public function setSourceId($sourceId): void
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
    public function setTokens($tokens): void
    {
        $this->tokens = $tokens;
    }
}
