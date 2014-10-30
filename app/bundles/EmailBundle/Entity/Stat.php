<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use Mautic\CoreBundle\Entity\IpAddress;
use Mautic\LeadBundle\Entity\Lead;

/**
 * Class Stats
 * @ORM\Table(name="email_stats")
 * @ORM\Entity(repositoryClass="Mautic\EmailBundle\Entity\StatRepository")
 * @Serializer\ExclusionPolicy("all")
 */
class Stat
{

    /**
     * @ORM\Column(type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="AUTO")
     * @Serializer\Expose
     * @Serializer\Since("1.0")
     * @Serializer\Groups({"full"})
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="Email", inversedBy="stats")
     * @ORM\JoinColumn(name="email_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     * @Serializer\Expose
     * @Serializer\Since("1.0")
     * @Serializer\Groups({"full"})
     **/
    private $email;

    /**
     * @ORM\ManyToOne(targetEntity="Mautic\LeadBundle\Entity\Lead")
     * @ORM\JoinColumn(name="lead_id", referencedColumnName="id", nullable=true)
     * @Serializer\Expose
     * @Serializer\Since("1.0")
     * @Serializer\Groups({"full"})
     **/
    private $lead;

    /**
     * @ORM\Column(name="email_address", type="string")
     */
    private $emailAddress;
    /**
     * @ORM\ManyToOne(targetEntity="Mautic\LeadBundle\Entity\LeadList")
     * @ORM\JoinColumn(name="list_id", referencedColumnName="id", nullable=true)
     * @Serializer\Expose
     * @Serializer\Since("1.0")
     * @Serializer\Groups({"full"})
     **/
    private $list;

    /**
     * @ORM\ManyToOne(targetEntity="Mautic\CoreBundle\Entity\IpAddress", cascade={"merge", "persist", "refresh", "detach"})
     * @ORM\JoinColumn(name="ip_id", referencedColumnName="id", nullable=true)
     * @Serializer\Expose
     * @Serializer\Since("1.0")
     * @Serializer\Groups({"full"})
     */
    private $ipAddress;

    /**
     * @ORM\Column(name="date_sent", type="datetime")
     * @Serializer\Expose
     * @Serializer\Since("1.0")
     * @Serializer\Groups({"full"})
     */
    private $dateSent;

    /**
     * @ORM\Column(name="is_read", type="boolean")
     * @Serializer\Expose
     * @Serializer\Since("1.0")
     * @Serializer\Groups({"full"})
     */
    private $isRead = false;

    /**
     * @ORM\Column(name="is_failed", type="boolean")
     * @Serializer\Expose
     * @Serializer\Since("1.0")
     * @Serializer\Groups({"full"})
     */
    private $isFailed = false;

    /**
     * @ORM\Column(name="viewed_in_browser", type="boolean")
     * @Serializer\Expose
     * @Serializer\Since("1.0")
     * @Serializer\Groups({"full"})
     */
    private $viewedInBrowser = false;

    /**
     * @ORM\Column(name="date_read", type="datetime", nullable=true)
     * @Serializer\Expose
     * @Serializer\Since("1.0")
     * @Serializer\Groups({"full"})
     */
    private $dateRead;

    /**
     * @ORM\Column(name="tracking_hash", type="string", nullable=true)
     */
    private $trackingHash;

    /**
     * @ORM\Column(name="retry_count", type="string", nullable=true)
     */
    private $retryCount = 0;

    /**
     * @return mixed
     */
    public function getDateRead ()
    {
        return $this->dateRead;
    }

    /**
     * @param mixed $dateRead
     */
    public function setDateRead ($dateRead)
    {
        $this->dateRead = $dateRead;
    }

    /**
     * @return mixed
     */
    public function getDateSent ()
    {
        return $this->dateSent;
    }

    /**
     * @param mixed $dateSent
     */
    public function setDateSent ($dateSent)
    {
        $this->dateSent = $dateSent;
    }

    /**
     * @return mixed
     */
    public function getEmail ()
    {
        return $this->email;
    }

    /**
     * @param mixed $email
     */
    public function setEmail (Email $email)
    {
        $this->email = $email;
    }

    /**
     * @return mixed
     */
    public function getId ()
    {
        return $this->id;
    }

    /**
     * @return mixed
     */
    public function getIpAddress ()
    {
        return $this->ipAddress;
    }

    /**
     * @param mixed $ip
     */
    public function setIpAddress (IpAddress $ip)
    {
        $this->ipAddress = $ip;
    }

    /**
     * @return mixed
     */
    public function getIsRead ()
    {
        return $this->isRead;
    }

    /**
     * @return mixed
     */
    public function isRead()
    {
        return $this->getIsRead();
    }

    /**
     * @param mixed $isRead
     */
    public function setIsRead ($isRead)
    {
        $this->isRead = $isRead;
    }

    /**
     * @return mixed
     */
    public function getLead ()
    {
        return $this->lead;
    }

    /**
     * @param mixed $lead
     */
    public function setLead (Lead $lead)
    {
        $this->lead = $lead;
    }

    /**
     * @return mixed
     */
    public function getTrackingHash ()
    {
        return $this->trackingHash;
    }

    /**
     * @param mixed $trackingHash
     */
    public function setTrackingHash ($trackingHash)
    {
        $this->trackingHash = $trackingHash;
    }

    /**
     * @return mixed
     */
    public function getList ()
    {
        return $this->list;
    }

    /**
     * @param mixed $list
     */
    public function setList ($list)
    {
        $this->list = $list;
    }

    /**
     * @return mixed
     */
    public function getRetryCount ()
    {
        return $this->retryCount;
    }

    /**
     * @param mixed $retryCount
     */
    public function setRetryCount ($retryCount)
    {
        $this->retryCount = $retryCount;
    }

    /**
     *
     */
    public function upRetryCount()
    {
        $this->retryCount++;
    }

    /**
     * @return mixed
     */
    public function getIsFailed ()
    {
        return $this->isFailed;
    }

    /**
     * @param mixed $isFailed
     */
    public function setIsFailed ($isFailed)
    {
        $this->isFailed = $isFailed;
    }

    /**
     * @return mixed
     */
    public function isFailed()
    {
        return $this->getIsFailed();
    }

    /**
     * @return mixed
     */
    public function getEmailAddress ()
    {
        return $this->emailAddress;
    }

    /**
     * @param mixed $emailAddress
     */
    public function setEmailAddress ($emailAddress)
    {
        $this->emailAddress = $emailAddress;
    }

    /**
     * @return mixed
     */
    public function getViewedInBrowser ()
    {
        return $this->viewedInBrowser;
    }

    /**
     * @param mixed $viewedInBrowser
     */
    public function setViewedInBrowser ($viewedInBrowser)
    {
        $this->viewedInBrowser = $viewedInBrowser;
    }
}
