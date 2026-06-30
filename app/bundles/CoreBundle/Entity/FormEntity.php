<?php

namespace Mautic\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Mautic\ApiBundle\Serializer\Driver\ApiMetadataDriver;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Mautic\CoreBundle\Helper\DateTimeHelper;
use Mautic\UserBundle\Entity\User;
use Symfony\Component\Serializer\Attribute\Groups;

class FormEntity extends CommonEntity
{
    /**
     * @var bool
     */
    #[Groups([
        'category:read', 'category:write',
        'notification:read', 'notification:write',
        'company:read', 'company:write',
        'leadfield:read', 'leadfield:write',
        'page:read', 'page:write',
        'campaign:read', 'campaign:write',
        'point:read', 'point:write',
        'trigger:read', 'trigger:write',
        'message:read', 'message:write',
        'focus:read', 'focus:write',
        'sms:read', 'sms:write',
        'asset:read', 'asset:write',
        'dynamicContent:read', 'dynamicContent:write',
        'form:read', 'form:write',
        'stage:read', 'stage:write',
        'segment:read', 'segment:write',
        'email:read', 'email:write',
    ])]
    private $isPublished = true;

    /**
     * @var \DateTimeInterface|null
     */
    #[Groups([
        'category:read', 'category:write',
        'notification:read', 'notification:write',
        'company:read', 'company:write',
        'leadfield:read', 'leadfield:write',
        'page:read', 'page:write',
        'campaign:read', 'campaign:write',
        'point:read', 'point:write',
        'trigger:read', 'trigger:write',
        'message:read', 'message:write',
        'focus:read', 'focus:write',
        'asset:read', 'asset:write',
        'sms:read', 'sms:write',
        'segment:read', 'segment:write',
        'email:read', 'email:write',
        'dynamicContent:read', 'dynamicContent:write',
        'form:read', 'form:write',
        'stage:read', 'stage:write',
    ])]
    private $dateAdded;

    /**
     * @var int|null
     */
    private $createdBy;

    /**
     * @var string|null
     */
    private $createdByUser;

    /**
     * @var \DateTimeInterface|null
     */
    #[Groups([
        'category:read', 'category:write',
        'notification:read', 'notification:write',
        'company:read', 'company:write',
        'leadfield:read', 'leadfield:write',
        'page:read', 'page:write',
        'campaign:read', 'campaign:write',
        'point:read', 'point:write',
        'trigger:read', 'trigger:write',
        'message:read', 'message:write',
        'focus:read', 'focus:write',
        'dynamicContent:read', 'dynamicContent:write',
        'form:read', 'form:write',
        'stage:read', 'stage:write',
        'segment:read', 'segment:write',
        'asset:read', 'asset:write',
    ])]
    private $dateModified;

    /**
     * @var int|null
     */
    private $modifiedBy;

    /**
     * @var string|null
     */
    private $modifiedByUser;

    /**
     * @var \DateTimeInterface|null
     */
    private $checkedOut;

    /**
     * @var int|null
     */
    private $checkedOutBy;

    /**
     * @var string|null
     */
    private $checkedOutByUser;

    /**
     * @var array
     */
    protected $changes = [];

    /**
     * @var bool
     */
    protected $new = false;

    /**
     * @var int|null
     */
    public $deletedId;

    public static function loadMetadata(ORM\ClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setMappedSuperClass();

        $builder->createField('isPublished', 'boolean')
            ->columnName('is_published')
            ->build();

        $builder->addDateAdded(true);

        $builder->createField('createdBy', 'integer')
            ->columnName('created_by')
            ->nullable()
            ->build();

        $builder->createField('createdByUser', 'string')
            ->columnName('created_by_user')
            ->nullable()
            ->build();

        $builder->createField('dateModified', 'datetime')
            ->columnName('date_modified')
            ->nullable()
            ->build();

        $builder->createField('modifiedBy', 'integer')
            ->columnName('modified_by')
            ->nullable()
            ->build();

        $builder->createField('modifiedByUser', 'string')
            ->columnName('modified_by_user')
            ->nullable()
            ->build();

        $builder->createField('checkedOut', 'datetime')
            ->columnName('checked_out')
            ->nullable()
            ->build();

        $builder->createField('checkedOutBy', 'integer')
            ->columnName('checked_out_by')
            ->nullable()
            ->build();

        $builder->createField('checkedOutByUser', 'string')
            ->columnName('checked_out_by_user')
            ->nullable()
            ->build();
    }

    /**
     * Prepares the metadata for API usage.
     */
    public static function loadApiMetadata(ApiMetadataDriver $metadata): void
    {
        $metadata->setGroupPrefix('publish')
            ->addListProperties(
                [
                    'isPublished',
                    'dateAdded',
                    'dateModified',
                ]
            )
            ->addProperties(
                [
                    'createdBy',
                    'createdByUser',
                    'dateModified',
                    'modifiedBy',
                    'modifiedByUser',
                ]
            )
            ->build();
    }

    /**
     * Clear dates on clone.
     */
    public function __clone()
    {
        $this->dateAdded    = null;
        $this->dateModified = new \DateTime();
        $this->checkedOut   = null;
        $this->isPublished  = false;
        $this->createdBy    = null;
        $this->changes      = [];
    }

    /**
     * Check publish status with option to check against category, publish up and down dates.
     *
     * @param bool $checkPublishStatus
     * @param bool $checkCategoryStatus
     *
     * @return bool
     */
    public function isPublished($checkPublishStatus = true, $checkCategoryStatus = true)
    {
        if ($checkPublishStatus && method_exists($this, 'getPublishUp')) {
            $status = $this->getPublishStatus();
            if ('published' == $status) {
                // check to see if there is a category to check
                if ($checkCategoryStatus && method_exists($this, 'getCategory')) {
                    $category = $this->getCategory();
                    if (null !== $category && !$category->isPublished()) {
                        return false;
                    }
                }
            }

            return 'published' === $status;
        }

        return $this->getIsPublished();
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
     * @return \DateTimeInterface|null
     */
    public function getDateAdded()
    {
        return $this->dateAdded;
    }

    /**
     * Set dateModified.
     *
     * @param \DateTime $dateModified
     */
    public function setDateModified($dateModified): static
    {
        $this->isChanged('dateModified', $dateModified);
        $this->dateModified = $dateModified;

        return $this;
    }

    /**
     * Get dateModified.
     *
     * @return \DateTimeInterface|null
     */
    public function getDateModified()
    {
        return $this->dateModified;
    }

    /**
     * Set checkedOut.
     *
     * @param \DateTime $checkedOut
     */
    public function setCheckedOut($checkedOut): static
    {
        $this->checkedOut = $checkedOut;

        return $this;
    }

    /**
     * Get checkedOut.
     *
     * @return \DateTimeInterface|null
     */
    public function getCheckedOut()
    {
        return $this->checkedOut;
    }

    /**
     * @param User|int|null $createdBy
     */
    public function setCreatedBy($createdBy = null): static
    {
        if (null != $createdBy && !$createdBy instanceof User) {
            $this->createdBy = $createdBy;
        } else {
            $this->createdBy = (null != $createdBy) ? $createdBy->getId() : null;
            if (null != $createdBy) {
                $this->createdByUser = $createdBy->getName();
            }
        }

        return $this;
    }

    /**
     * Get createdBy.
     *
     * @return int|null
     */
    public function getCreatedBy()
    {
        return $this->createdBy;
    }

    /**
     * Set modifiedBy.
     *
     * @param User|int|null $modifiedBy
     */
    public function setModifiedBy($modifiedBy = null): static
    {
        if (null != $modifiedBy && !$modifiedBy instanceof User) {
            $this->modifiedBy = $modifiedBy;
        } else {
            $this->modifiedBy = (null != $modifiedBy) ? $modifiedBy->getId() : null;

            if (null != $modifiedBy) {
                $this->modifiedByUser = $modifiedBy->getName();
            }
        }

        return $this;
    }

    /**
     * Get modifiedBy.
     *
     * @return int|null
     */
    public function getModifiedBy()
    {
        return $this->modifiedBy;
    }

    /**
     * Set checkedOutBy.
     *
     * @param User $checkedOutBy
     */
    public function setCheckedOutBy($checkedOutBy = null): static
    {
        if (null != $checkedOutBy && !$checkedOutBy instanceof User) {
            $this->checkedOutBy = $checkedOutBy;
        } else {
            $this->checkedOutBy = (null != $checkedOutBy) ? $checkedOutBy->getId() : null;

            if (null != $checkedOutBy) {
                $this->checkedOutByUser = $checkedOutBy->getName();
            }
        }

        return $this;
    }

    /**
     * Get checkedOutBy.
     *
     * @return int|null
     */
    public function getCheckedOutBy()
    {
        return $this->checkedOutBy;
    }

    /**
     * Set isPublished.
     *
     * @param bool $isPublished
     */
    public function setIsPublished($isPublished): static
    {
        $this->isChanged('isPublished', (bool) $isPublished);

        $this->isPublished = (bool) $isPublished;

        return $this;
    }

    /**
     * Get isPublished.
     *
     * @return bool
     */
    public function getIsPublished()
    {
        return $this->isPublished;
    }

    /**
     * Check the publish status of an entity based on publish up and down datetimes.
     *
     * @return string early|expired|published|unpublished
     *
     * @throws \BadMethodCallException
     */
    public function getPublishStatus()
    {
        $dt      = new DateTimeHelper();
        $current = $dt->getLocalDateTime();
        if (!$this->isPublished(false)) {
            return 'unpublished';
        }

        $status = 'published';
        if (method_exists($this, 'getPublishUp')) {
            $up = $this->getPublishUp();
            if (!empty($up) && $current < $up) {
                $status = 'pending';
            }
        }
        if (method_exists($this, 'getPublishDown')) {
            $down = $this->getPublishDown();
            if (!empty($down) && $current >= $down) {
                $status = 'expired';
            }
        }

        return $status;
    }

    /**
     * @return bool
     */
    public function isNew()
    {
        if ($this->new) {
            return true;
        }

        if (!method_exists($this, 'getId')) {
            return true;
        }

        return !$this->getId();
    }

    /**
     * Set this entity as new in case it has to be saved prior to the events.
     */
    public function setNew(): void
    {
        $this->new = true;
    }

    /**
     * @return string|null
     */
    public function getCheckedOutByUser()
    {
        return $this->checkedOutByUser;
    }

    /**
     * @return string|null
     */
    public function getCreatedByUser()
    {
        return $this->createdByUser;
    }

    /**
     * @return string|null
     */
    public function getModifiedByUser()
    {
        return $this->modifiedByUser;
    }

    /**
     * @param mixed $createdByUser
     */
    public function setCreatedByUser($createdByUser): static
    {
        $this->createdByUser = $createdByUser;

        return $this;
    }

    /**
     * @param mixed $modifiedByUser
     */
    public function setModifiedByUser($modifiedByUser): static
    {
        $this->modifiedByUser = $modifiedByUser;

        return $this;
    }

    /**
     * @param mixed $checkedOutByUser
     */
    public function setCheckedOutByUser($checkedOutByUser): static
    {
        $this->checkedOutByUser = $checkedOutByUser;

        return $this;
    }
}
