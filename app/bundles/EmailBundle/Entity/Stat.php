<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
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
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="Email", inversedBy="stats")
     * @ORM\JoinColumn(name="email_id", referencedColumnName="id", nullable=true, onDelete="CASCADE")
     **/
    private $email;

    /**
     * @ORM\ManyToOne(targetEntity="Mautic\LeadBundle\Entity\Lead")
     * @ORM\JoinColumn(name="lead_id", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     **/
    private $lead;

    /**
     * @ORM\Column(name="email_address", type="string")
     */
    private $emailAddress;

    /**
     * @ORM\ManyToOne(targetEntity="Mautic\LeadBundle\Entity\LeadList")
     * @ORM\JoinColumn(name="list_id", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     **/
    private $list;

    /**
     * @ORM\ManyToOne(targetEntity="Mautic\CoreBundle\Entity\IpAddress", cascade={"merge", "persist"})
     * @ORM\JoinColumn(name="ip_id", referencedColumnName="id", nullable=true)
     */
    private $ipAddress;

    /**
     * @ORM\Column(name="date_sent", type="datetime")
     */
    private $dateSent;

    /**
     * @ORM\Column(name="is_read", type="boolean")
     */
    private $isRead = false;

    /**
     * @ORM\Column(name="is_failed", type="boolean")
     */
    private $isFailed = false;

    /**
     * @ORM\Column(name="viewed_in_browser", type="boolean")
     */
    private $viewedInBrowser = false;

    /**
     * @ORM\Column(name="date_read", type="datetime", nullable=true)
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
     * @ORM\Column(name="source", type="string", nullable=true)
     */
    private $source;

    /**
     * @ORM\Column(name="source_id", type="integer", nullable=true)
     */
    private $sourceId;

    /**
     * @ORM\Column(type="array", nullable=true)
     */
    private $tokens = array();

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $copy;

    /**
     * @ORM\Column(type="integer", name="open_count", nullable=true)
     */
    private $openCount;

    /**
     * @ORM\Column(type="datetime", name="last_opened", nullable=true)
     */
    private $lastOpened;

    /**
     * @ORM\Column(type="array", name="open_details", nullable=true)
     */
    private $openDetails;

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
    public function setEmail (Email $email = null)
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
    public function setLead (Lead $lead = null)
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

    /**
     * @return mixed
     */
    public function getSource ()
    {
        return $this->source;
    }

    /**
     * @param mixed $source
     */
    public function setSource ($source)
    {
        $this->source = $source;
    }

    /**
     * @return mixed
     */
    public function getSourceId ()
    {
        return $this->sourceId;
    }

    /**
     * @param mixed $sourceId
     */
    public function setSourceId ($sourceId)
    {
        $this->sourceId = (int) $sourceId;
    }

    /**
     * @return mixed
     */
    public function getTokens ()
    {
        return $this->tokens;
    }

    /**
     * @param mixed $tokens
     */
    public function setTokens ($tokens)
    {
        $this->tokens = $tokens;
    }

    /**
     * @return mixed
     */
    public function getCopy()
    {
        return $this->copy;
    }

    /**
     * @param mixed $copy
     *
     * @return Stat
     */
    public function setCopy($copy)
    {
        $this->copy = $copy;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getOpenCount()
    {
        return $this->openCount;
    }

    /**
     * @param mixed $openCount
     *
     * @return Stat
     */
    public function setOpenCount($openCount)
    {
        $this->openCount = $openCount;

        return $this;
    }

    /**
     * @param $details
     */
    public function addOpenDetails($details)
    {
        $this->openDetails[] = $details;

        $this->openCount++;
    }

    /**
     * Up the sent count
     *
     * @return Stat
     */
    public function upOpenCount()
    {
        $count = (int) $this->openCount + 1;
        $this->openCount = $count;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getLastOpened()
    {
        return $this->lastOpened;
    }

    /**
     * @param mixed $lastOpened
     *
     * @return Stat
     */
    public function setLastOpened($lastOpened)
    {
        $this->lastOpened = $lastOpened;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getOpenDetails()
    {
        return $this->openDetails;
    }

    /**
     * @param mixed $openDetails
     *
     * @return Stat
     */
    public function setOpenDetails($openDetails)
    {
        $this->openDetails = $openDetails;

        return $this;
    }
}
