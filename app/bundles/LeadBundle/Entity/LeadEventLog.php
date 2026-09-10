<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Mautic\ApiBundle\Serializer\Driver\ApiMetadataDriver;

/**
 * Store here contact events.
 */
#[ORM\Entity(repositoryClass: LeadEventLogRepository::class)]
#[ORM\Table(name: 'lead_event_log')]
#[ORM\Index(columns: ['lead_id'], name: 'lead_id_index')]
#[ORM\Index(columns: ['object', 'object_id'], name: 'lead_object_index')]
#[ORM\Index(columns: ['bundle', 'object', 'action', 'object_id'], name: 'lead_timeline_index')]
#[ORM\Index(columns: ['bundle', 'object', 'action', 'object_id', 'date_added'], name: self::INDEX_SEARCH)]
#[ORM\Index(columns: ['action'], name: 'lead_timeline_action_index')]
#[ORM\Index(columns: ['date_added'], name: 'lead_date_added_index')]
#[ORM\ChangeTrackingPolicy('DEFERRED_EXPLICIT')]
class LeadEventLog
{
    /**
     * @var string
     */
    public const INDEX_SEARCH = 'IDX_SEARCH';

    /**
     * @var string
     */
    #[ORM\Id]
    #[ORM\Column(type: 'bigint', options: ['unsigned' => true])]
    #[ORM\GeneratedValue]
    protected $id;

    /**
     * @var Lead|null
     */
    #[ORM\ManyToOne(targetEntity: Lead::class, inversedBy: 'eventLog')]
    #[ORM\JoinColumn(name: 'lead_id', onDelete: 'CASCADE')]
    protected $lead;

    /**
     * @var int|null
     */
    #[ORM\Column(name: 'user_id', type: Types::INTEGER, nullable: true)]
    protected $userId;

    /**
     * @var string|null
     */
    #[ORM\Column(name: 'user_name', type: Types::STRING, length: 191, nullable: true)]
    protected $userName;

    /**
     * @var string|null
     */
    #[ORM\Column(type: Types::STRING, length: 191, nullable: true)]
    protected $bundle;

    /**
     * @var string|null
     */
    #[ORM\Column(type: Types::STRING, length: 191, nullable: true)]
    protected $object;

    /**
     * @var int|null
     */
    #[ORM\Column(name: 'object_id', type: Types::INTEGER, nullable: true)]
    protected $objectId;

    /**
     * @var string|null
     */
    #[ORM\Column(type: Types::STRING, length: 191, nullable: true)]
    protected $action;

    /**
     * @var \DateTimeInterface
     */
    #[ORM\Column(name: 'date_added', type: Types::DATETIME_MUTABLE)]
    protected $dateAdded;

    /**
     * @var array|null
     */
    #[ORM\Column(type: Types::JSON, nullable: true)]
    private $properties = [];

    public function __construct()
    {
        $this->setDateAdded(new \DateTime());
    }

    /**
     * Prepares the metadata for API usage.
     */
    public static function loadApiMetadata(ApiMetadataDriver $metadata): void
    {
        $metadata->setGroupPrefix('import')
            ->addListProperties(
                [
                    'id',
                    'leadId',
                    'userId',
                    'userName',
                    'bundle',
                    'object',
                    'action',
                    'objectId',
                    'dateAdded',
                    'properties',
                ]
            )
            ->build();
    }

    public function getId(): int
    {
        return (int) $this->id;
    }

    public function setLead(Lead $lead): static
    {
        $this->lead = $lead;

        return $this;
    }

    /**
     * @return Lead|null
     */
    public function getLead()
    {
        return $this->lead;
    }

    /**
     * @param int $userId
     */
    public function setUserId($userId): static
    {
        $this->userId = $userId;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * @param string $object
     */
    public function setObject($object): static
    {
        $this->object = $object;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getObject()
    {
        return $this->object;
    }

    /**
     * @param int $objectId
     */
    public function setObjectId($objectId): static
    {
        $this->objectId = $objectId;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getObjectId()
    {
        return $this->objectId;
    }

    /**
     * @param string $action
     */
    public function setAction($action): static
    {
        $this->action = $action;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getAction()
    {
        return $this->action;
    }

    public function setProperties(array $properties): static
    {
        $this->properties = $properties;

        return $this;
    }

    /**
     * Set one property into the properties array.
     *
     * @param string $key
     * @param string $value
     */
    public function addProperty($key, $value): static
    {
        $this->properties[$key] = $value;

        return $this;
    }

    /**
     * @return array|null
     */
    public function getProperties()
    {
        return $this->properties;
    }

    /**
     * @param \DateTime $dateAdded
     */
    public function setDateAdded($dateAdded): static
    {
        $this->dateAdded = $dateAdded;

        return $this;
    }

    /**
     * @return \DateTimeInterface
     */
    public function getDateAdded()
    {
        return $this->dateAdded;
    }

    /**
     * @param string $bundle
     */
    public function setBundle($bundle): static
    {
        $this->bundle = $bundle;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getBundle()
    {
        return $this->bundle;
    }

    /**
     * @param string $userName
     */
    public function setUserName($userName): static
    {
        $this->userName = $userName;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getUserName()
    {
        return $this->userName;
    }
}
