<?php

namespace Mautic\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Mautic\ApiBundle\Serializer\Driver\ApiMetadataDriver;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Mautic\CoreBundle\Helper\DateTimeHelper;
use Mautic\UserBundle\Entity\User;

class FormEntity extends CommonEntity
{
    /**
     * @var bool
     */
    private $isPublished = true;

    /**
     * @var \DateTimeInterface|null
     */
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

            return ('published' == $status) ? true : false;
        }

        return $this->getIsPublished();
    }

    /**
     * Set dateAdded.
     *
     * @param \DateTime $dateAdded
     *
     * @return $this
     */
    public function setDateAdded($dateAdded)
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
     *
     * @return $this
     */
    public function setDateModified($dateModified)
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
     *
     * @return $this
     */
    public function setCheckedOut($checkedOut)
    {
        $this->checkedOut = $checkedOut;

        return $this;
    }

    /**
     * Get checkedOut.
     *
     * @return \DateTimeInterface
     */
    public function getCheckedOut()
    {
        return $this->checkedOut;
    }

    /**
     * @param User|int|null $createdBy
     *
     * @return $this
     */
    public function setCreatedBy($createdBy = null)
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
     * @return int
     */
    public function getCreatedBy()
    {
        return $this->createdBy;
    }

    /**
     * Set modifiedBy.
     *
     * @param User|int|null $modifiedBy
     *
     * @return mixed
     */
    public function setModifiedBy($modifiedBy = null)
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
     *
     * @return mixed
     */
    public function setCheckedOutBy($checkedOutBy = null)
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
     * @return User
     */
    public function getCheckedOutBy()
    {
        return $this->checkedOutBy;
    }

    /**
     * Set isPublished.
     *
     * @param bool $isPublished
     *
     * @return $this
     */
    public function setIsPublished($isPublished)
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
     * @return string
     */
    public function getCheckedOutByUser()
    {
        return $this->checkedOutByUser;
    }

    /**
     * @return string
     */
    public function getCreatedByUser()
    {
        return $this->createdByUser;
    }

    /**
     * @return string
     */
    public function getModifiedByUser()
    {
        return $this->modifiedByUser;
    }

    /**
     * @param mixed $createdByUser
     *
     * @return $this
     */
    public function setCreatedByUser($createdByUser)
    {
        $this->createdByUser = $createdByUser;

        return $this;
    }

    /**
     * @param mixed $modifiedByUser
     *
     * @return $this
     */
    public function setModifiedByUser($modifiedByUser)
    {
        $this->modifiedByUser = $modifiedByUser;

        return $this;
    }

    /**
     * @param mixed $checkedOutByUser
     *
     * @return $this
     */
    public function setCheckedOutByUser($checkedOutByUser)
    {
        $this->checkedOutByUser = $checkedOutByUser;

        return $this;
    }
}
