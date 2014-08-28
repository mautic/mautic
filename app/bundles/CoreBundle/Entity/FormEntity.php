<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use Mautic\CoreBundle\Helper\DateTimeHelper;

/**
 * Class FormEntity
 * @ORM\MappedSuperclass
 * @ORM\HasLifecycleCallbacks
 * @Serializer\ExclusionPolicy("all")
 */
class FormEntity
{

    /**
     * @ORM\Column(name="is_published", type="boolean")
     * @Serializer\Expose
     * @Serializer\Since("1.0")
     * @Serializer\Groups({"full"})
     */
    private $isPublished = true;

    /**
     * @ORM\Column(name="date_added", type="datetime", nullable=true)
     */
    private $dateAdded;

    /**
     * @ORM\ManyToOne(targetEntity="Mautic\UserBundle\Entity\User")
     * @ORM\JoinColumn(name="created_by", referencedColumnName="id", nullable=true)
     */
    private $createdBy;

    /**
     * @ORM\Column(name="date_modified", type="datetime", nullable=true)
     */
    private $dateModified;

    /**
     * @ORM\ManyToOne(targetEntity="Mautic\UserBundle\Entity\User")
     * @ORM\JoinColumn(name="modified_by", referencedColumnName="id", nullable=true)
     */
    private $modifiedBy;

    /**
     * @ORM\Column(name="checked_out", type="datetime", nullable=true)
     */
    private $checkedOut;

    /**
     * @ORM\ManyToOne(targetEntity="Mautic\UserBundle\Entity\User")
     * @ORM\JoinColumn(name="checked_out_by", referencedColumnName="id", nullable=true)
     */
    private $checkedOutBy;

    /**
     * @var
     */
    protected $changes;

    /**
     * Wrapper function for isProperty methods
     *
     * @param $name
     * @param $arguments
     */
    public function __call($name, $arguments)
    {
        if (strpos($name, 'is') === 0 && method_exists($this, 'get' . ucfirst($name))) {
            return $this->{'get' . ucfirst($name)}();
        } elseif ($name == 'getName' && method_exists($this, 'getTitle')) {
            return $this->getTitle();
        }

        throw new \InvalidArgumentException('Method ' . $name . ' not exists');
    }

    /**
     * Check publish status with option to check against publish up and down dates
     *
     * @param bool $checkPublishStatus
     */
    public function isPublished($checkPublishStatus = true)
    {
        if ($checkPublishStatus && method_exists($this, 'getPublishUp')) {
            return ($this->getPublishStatus() == 'published') ? true : false;
        } else {
            return $this->getIsPublished();
        }
    }

    /**
     * @param $prop
     * @param $val
     */
    protected function isChanged($prop, $val)
    {
        $getter  = "get" . ucfirst($prop);
        $current = $this->$getter();
        if ($current != $val) {
            $this->changes[$prop] = array($current, $val);
        }
    }

    public function getChanges()
    {
        return $this->changes;
    }

    public function __toString()
    {
        return get_called_class()  . " with ID #" . $this->getId();
    }

    /**
     * Set dateAdded
     *
     * @param \DateTime $dateAdded
     * @return LeadList
     */
    public function setDateAdded ($dateAdded)
    {
        $this->dateAdded = $dateAdded;

        return $this;
    }

    /**
     * Get dateAdded
     *
     * @return \DateTime
     */
    public function getDateAdded ()
    {
        return $this->dateAdded;
    }

    /**
     * Set dateModified
     *
     * @param \DateTime $dateModified
     * @return LeadList
     */
    public function setDateModified ($dateModified)
    {
        $this->dateModified = $dateModified;

        return $this;
    }

    /**
     * Get dateModified
     *
     * @return \DateTime
     */
    public function getDateModified ()
    {
        return $this->dateModified;
    }

    /**
     * Set checkedOut
     *
     * @param \DateTime $checkedOut
     * @return LeadList
     */
    public function setCheckedOut ($checkedOut)
    {
        $this->checkedOut = $checkedOut;

        return $this;
    }

    /**
     * Get checkedOut
     *
     * @return \DateTime
     */
    public function getCheckedOut ()
    {
        return $this->checkedOut;
    }

    /**
     * Set createdBy
     *
     * @param \Mautic\UserBundle\Entity\User $createdBy
     * @return LeadList
     */
    public function setCreatedBy (\Mautic\UserBundle\Entity\User $createdBy = null)
    {
        $this->createdBy = $createdBy;

        return $this;
    }

    /**
     * Get createdBy
     *
     * @return \Mautic\UserBundle\Entity\User
     */
    public function getCreatedBy ()
    {
        return $this->createdBy;
    }

    /**
     * Set modifiedBy
     *
     * @param \Mautic\UserBundle\Entity\User $modifiedBy
     * @return LeadList
     */
    public function setModifiedBy (\Mautic\UserBundle\Entity\User $modifiedBy = null)
    {
        $this->modifiedBy = $modifiedBy;

        return $this;
    }

    /**
     * Get modifiedBy
     *
     * @return \Mautic\UserBundle\Entity\User
     */
    public function getModifiedBy ()
    {
        return $this->modifiedBy;
    }

    /**
     * Set checkedOutBy
     *
     * @param \Mautic\UserBundle\Entity\User $checkedOutBy
     * @return LeadList
     */
    public function setCheckedOutBy (\Mautic\UserBundle\Entity\User $checkedOutBy = null)
    {
        $this->checkedOutBy = $checkedOutBy;

        return $this;
    }

    /**
     * Get checkedOutBy
     *
     * @return \Mautic\UserBundle\Entity\User
     */
    public function getCheckedOutBy ()
    {
        return $this->checkedOutBy;
    }

    /**
     * Set isPublished
     *
     * @param boolean $isPublished
     * @return Action
     */
    public function setIsPublished($isPublished)
    {
        $this->isChanged('isPublished', $isPublished);

        $this->isPublished = $isPublished;

        return $this;
    }

    /**
     * Get isPublished
     *
     * @return boolean
     */
    public function getIsPublished()
    {
        return $this->isPublished;
    }

    /**
     * Check the publish status of an entity based on publish up and down datetimes
     *
     * @return string early|expired|published|unpublished
     * @throws \BadMethodCallException
     */
    public function getPublishStatus()
    {
        $dt      = new DateTimeHelper();
        $current = $dt->getLocalDateTime();
        if (!$this->isPublished(false)) {
            return 'unpublished';
        } else {
            $status  =  'published';
            if (method_exists($this, 'getPublishUp')) {
                $up = $this->getPublishUp();
                if (!empty($up) && $current <= $up) {
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
    }

    public function isNew()
    {
        $id = $this->getId();
        return (empty($id)) ? true : false;
    }
}
