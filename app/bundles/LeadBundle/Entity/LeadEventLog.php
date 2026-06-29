<?php

namespace Mautic\LeadBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Mautic\ApiBundle\Serializer\Driver\ApiMetadataDriver;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;

/**
 * Store here contact events.
 */
class LeadEventLog
{
    /**
     * @var string
     */
    public const INDEX_SEARCH = 'IDX_SEARCH';

    /**
     * @var string
     */
    protected $id;

    /**
     * @var Lead|null
     */
    protected $lead;

    /**
     * @var int|null
     */
    protected $userId;

    /**
     * @var string|null
     */
    protected $userName;

    /**
     * @var string|null
     */
    protected $bundle;

    /**
     * @var string|null
     */
    protected $object;

    /**
     * @var int|null
     */
    protected $objectId;

    /**
     * @var string|null
     */
    protected $action;

    /**
     * @var \DateTimeInterface
     */
    protected $dateAdded;

    /**
     * @var array|null
     */
    private $properties = [];

    public function __construct()
    {
        $this->setDateAdded(new \DateTime());
    }

    public static function loadMetadata(ORM\ClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);
        $builder->setTable('lead_event_log')
            ->setCustomRepositoryClass(LeadEventLogRepository::class)
            ->addIndex(['lead_id'], 'lead_id_index')
            ->addIndex(['object', 'object_id'], 'lead_object_index')
            ->addIndex(['bundle', 'object', 'action', 'object_id'], 'lead_timeline_index')
            ->addIndex(['bundle', 'object', 'action', 'object_id', 'date_added'], self::INDEX_SEARCH)
            ->addIndex(['action'], 'lead_timeline_action_index')
            ->addIndex(['date_added'], 'lead_date_added_index')
            ->addBigIntIdField()
            ->addNullableField('userId', Types::INTEGER, 'user_id')
            ->addNullableField('userName', Types::STRING, 'user_name')
            ->addNullableField('bundle', Types::STRING)
            ->addNullableField('object', Types::STRING)
            ->addNullableField('action', Types::STRING)
            ->addNullableField('objectId', Types::INTEGER, 'object_id')
            ->addNamedField('dateAdded', Types::DATETIME_MUTABLE, 'date_added')
            ->addNullableField('properties', Types::JSON);

        $builder->createManyToOne('lead', Lead::class)
            ->addJoinColumn('lead_id', 'id', true, false, 'CASCADE')
            ->inversedBy('eventLog')
            ->build();
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

    /**
     * Get id.
     */
    public function getId(): int
    {
        return (int) $this->id;
    }

    /**
     * Set lead.
     */
    public function setLead(Lead $lead): static
    {
        $this->lead = $lead;

        return $this;
    }

    /**
     * Get lead.
     *
     * @return Lead|null
     */
    public function getLead()
    {
        return $this->lead;
    }

    /**
     * Set userId.
     *
     * @param int $userId
     */
    public function setUserId($userId): static
    {
        $this->userId = $userId;

        return $this;
    }

    /**
     * Get userId.
     *
     * @return int|null
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * Set object.
     *
     * @param string $object
     */
    public function setObject($object): static
    {
        $this->object = $object;

        return $this;
    }

    /**
     * Get object.
     *
     * @return string|null
     */
    public function getObject()
    {
        return $this->object;
    }

    /**
     * Set objectId.
     *
     * @param int $objectId
     */
    public function setObjectId($objectId): static
    {
        $this->objectId = $objectId;

        return $this;
    }

    /**
     * Get objectId.
     *
     * @return int|null
     */
    public function getObjectId()
    {
        return $this->objectId;
    }

    /**
     * Set action.
     *
     * @param string $action
     */
    public function setAction($action): static
    {
        $this->action = $action;

        return $this;
    }

    /**
     * Get action.
     *
     * @return string|null
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * Set properties.
     */
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
     * Get properties.
     *
     * @return array|null
     */
    public function getProperties()
    {
        return $this->properties;
    }

    /**
     * Set dateAdded.
     *
     * @param \DateTime $dateAdded
     */
    public function setDateAdded($dateAdded): static
    {
        $this->dateAdded = $dateAdded;

        return $this;
    }

    /**
     * Get dateAdded.
     *
     * @return \DateTimeInterface
     */
    public function getDateAdded()
    {
        return $this->dateAdded;
    }

    /**
     * Set bundle.
     *
     * @param string $bundle
     */
    public function setBundle($bundle): static
    {
        $this->bundle = $bundle;

        return $this;
    }

    /**
     * Get bundle.
     *
     * @return string|null
     */
    public function getBundle()
    {
        return $this->bundle;
    }

    /**
     * Set userName.
     *
     * @param string $userName
     */
    public function setUserName($userName): static
    {
        $this->userName = $userName;

        return $this;
    }

    /**
     * Get userName.
     *
     * @return string|null
     */
    public function getUserName()
    {
        return $this->userName;
    }
}
