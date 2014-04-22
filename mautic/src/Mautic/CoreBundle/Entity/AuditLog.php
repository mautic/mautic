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
use Doctrine\Common\Collections\Criteria;


/**
 * Class AuditLog
 *
 * @package Mautic\CoreBundle\Entity
 * @ORM\Table(name="audit_log")
 * @ORM\Entity(repositoryClass="Mautic\CoreBundle\Entity\AuditLogRepository")
 * @ORM\HasLifecycleCallbacks
 */
class AuditLog
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\Column(name="user_id", type="integer")
     */
    protected $userId;

    /**
     * @ORM\Column(type="string", length=50)
     */
    protected $bundle;

    /**
     * @ORM\Column(type="string", length=50)
     */
    protected $object;

    /**
     * @ORM\Column(name="object_id", type="integer")
     */
    protected $objectId;

    /**
     * @ORM\Column(type="string", length=50)
     */
    protected $action;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    protected $details;

    /**
     * @ORM\Column(name="date_added", type="datetime")
     */
    protected $dateAdded;

    /**
     * @ORM\Column(name="ip_address", type="string", length=15)
     */
    protected $ipAddress;

    /**
     * Sets the Date/Time for new entities
     *
     * @ORM\PrePersist
     */
    public function onPrePersistSetDateAdded()
    {
        if (!$this->getId()) {
            $this->setDateAdded(new \DateTime());
        }
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set userId
     *
     * @param integer $userId
     * @return AuditLog
     */
    public function setUserId($userId)
    {
        $this->userId = $userId;

        return $this;
    }

    /**
     * Get userId
     *
     * @return integer
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * Set object
     *
     * @param string $object
     * @return AuditLog
     */
    public function setObject($object)
    {
        $this->object = $object;

        return $this;
    }

    /**
     * Get object
     *
     * @return string
     */
    public function getObject()
    {
        return $this->object;
    }

    /**
     * Set objectId
     *
     * @param integer $objectId
     * @return AuditLog
     */
    public function setObjectId($objectId)
    {
        $this->objectId = $objectId;

        return $this;
    }

    /**
     * Get objectId
     *
     * @return integer
     */
    public function getObjectId()
    {
        return $this->objectId;
    }

    /**
     * Set action
     *
     * @param string $action
     * @return AuditLog
     */
    public function setAction($action)
    {
        $this->action = $action;

        return $this;
    }

    /**
     * Get action
     *
     * @return string
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * Set details
     *
     * @param string $details
     * @return AuditLog
     */
    public function setDetails($details)
    {
        $this->details = $details;

        return $this;
    }

    /**
     * Get details
     *
     * @return string
     */
    public function getDetails()
    {
        return $this->details;
    }

    /**
     * Set dateAdded
     *
     * @param \DateTime $dateAdded
     * @return AuditLog
     */
    public function setDateAdded($dateAdded)
    {
        $this->dateAdded = $dateAdded;

        return $this;
    }

    /**
     * Get dateAdded
     *
     * @return \DateTime
     */
    public function getDateAdded()
    {
        return $this->dateAdded;
    }

    /**
     * Set ipAddress
     *
     * @param string $ipAddress
     * @return AuditLog
     */
    public function setIpAddress($ipAddress)
    {
        $this->ipAddress = $ipAddress;

        return $this;
    }

    /**
     * Get ipAddress
     *
     * @return string
     */
    public function getIpAddress()
    {
        return $this->ipAddress;
    }

    /**
     * Set bundle
     *
     * @param string $bundle
     * @return AuditLog
     */
    public function setBundle($bundle)
    {
        $this->bundle = $bundle;

        return $this;
    }

    /**
     * Get bundle
     *
     * @return string 
     */
    public function getBundle()
    {
        return $this->bundle;
    }
}
