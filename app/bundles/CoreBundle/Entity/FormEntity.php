<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Mautic\ApiBundle\Serializer\Driver\ApiMetadataDriver;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Mautic\CoreBundle\Helper\DateTimeHelper;
use Mautic\UserBundle\Entity\User;

/**
 * Class FormEntity.
 */
class FormEntity extends CommonEntity
{
    /**
     * @var bool
     */
    private $isPublished = true;

    /**
     * @var null|\DateTime
     */
    private $dateAdded = null;

    /**
     * @var null|int
     */
    private $createdBy;

    /**
     * @var null|string
     */
    private $createdByUser;

    /**
     * @var null|\DateTime
     */
    private $dateModified;

    /**
     * var null|int.
     */
    private $modifiedBy;

    /**
     * @var null|string
     */
    private $modifiedByUser;

    /**
     * @var null|\DateTime
     */
    private $checkedOut;

    /**
     * @var null|int
     */
    private $checkedOutBy;

    /**
     * @var null|string
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
     * @var
     */
    public $deletedId;

    /**
     * @param ORM\ClassMetadata $metadata
     */
    public static function loadMetadata(ORM\ClassMetadata $metadata)
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
     *
     * @param $metadata
     */
    public static function loadApiMetadata(ApiMetadataDriver $metadata)
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
        $this->dateModified = null;
        $this->checkedOut   = null;
        $this->isPublished  = false;
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
            if ($status == 'published') {
                //check to see if there is a category to check
                if ($checkCategoryStatus && method_exists($this, 'getCategory')) {
                    $category = $this->getCategory();
                    if ($category !== null && !$category->isPublished()) {
                        return false;
                    }
                }
            }

            return ($status == 'published') ? true : false;
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
     * @return \DateTime
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
        $this->dateModified = $dateModified;

        return $this;
    }

    /**
     * Get dateModified.
     *
     * @return \DateTime
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
     * @return \DateTime
     */
    public function getCheckedOut()
    {
        return $this->checkedOut;
    }

    /**
     * Set createdBy.
     *
     * @param User $createdBy
     *
     * @return $this
     */
    public function setCreatedBy($createdBy = null)
    {
        if ($createdBy != null && !$createdBy instanceof User) {
            $this->createdBy = $createdBy;
        } else {
            $this->createdBy = ($createdBy != null) ? $createdBy->getId() : null;
            if ($createdBy != null) {
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
     * @param User $modifiedBy
     *
     * @return mixed
     */
    public function setModifiedBy($modifiedBy = null)
    {
        if ($modifiedBy != null && !$modifiedBy instanceof User) {
            $this->modifiedBy = $modifiedBy;
        } else {
            $this->modifiedBy = ($modifiedBy != null) ? $modifiedBy->getId() : null;

            if ($modifiedBy != null) {
                $this->modifiedByUser = $modifiedBy->getName();
            }
        }

        return $this;
    }

    /**
     * Get modifiedBy.
     *
     * @return User
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
        if ($checkedOutBy != null && !$checkedOutBy instanceof User) {
            $this->checkedOutBy = $checkedOutBy;
        } else {
            $this->checkedOutBy = ($checkedOutBy != null) ? $checkedOutBy->getId() : null;

            if ($checkedOutBy != null) {
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
        $this->isChanged('isPublished', $isPublished);

        $this->isPublished = $isPublished;

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

        $id = $this->getId();

        return (empty($id)) ? true : false;
    }

    /**
     * Set this entity as new in case it has to be saved prior to the events.
     */
    public function setNew()
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
