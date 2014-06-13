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

/**
 * Class FormEntity
 * @ORM\MappedSuperclass
 * @ORM\HasLifecycleCallbacks
 * @Serializer\ExclusionPolicy("all")
 */
class FormEntity
{

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
}
