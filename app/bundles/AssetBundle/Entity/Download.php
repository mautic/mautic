<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\AssetBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use Mautic\EmailBundle\Entity\Email;

/**
 * Class Download
 * @ORM\Table(name="asset_downloads")
 * @ORM\Entity(repositoryClass="Mautic\AssetBundle\Entity\DownloadRepository")
 * @Serializer\ExclusionPolicy("all")
 */
class Download
{

    /**
     * @ORM\Column(type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\Column(name="date_download", type="datetime")
     */
    private $dateDownload;

    /**
     * @ORM\ManyToOne(targetEntity="Asset")
     * @ORM\JoinColumn(name="asset_id", referencedColumnName="id", nullable=true, onDelete="CASCADE")
     */
    private $asset;

    /**
     * @ORM\ManyToOne(targetEntity="Mautic\CoreBundle\Entity\IpAddress", cascade={"merge", "persist"})
     * @ORM\JoinColumn(name="ip_id", referencedColumnName="id", nullable=false)
     */
    private $ipAddress;

    /**
     * @ORM\ManyToOne(targetEntity="Mautic\LeadBundle\Entity\Lead")
     * @ORM\JoinColumn(name="lead_id", referencedColumnName="id", nullable=true))
     */
    private $lead;

    /**
     * @ORM\Column(type="integer")
     */
    private $code;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $referer;

    /**
     * @ORM\Column(type="string", name="tracking_id")
     **/
    private $trackingId;

    /**
     * @ORM\Column(name="source", type="string", nullable=true)
     */
    private $source;

    /**
     * @ORM\Column(name="source_id", type="integer", nullable=true)
     */
    private $sourceId;

    /**
     * @ORM\ManyToOne(targetEntity="Mautic\EmailBundle\Entity\Email")
     * @ORM\JoinColumn(name="email_id", referencedColumnName="id", nullable=true, onDelete="SET NULL"))
     */
    private $email;

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
     * Set dateDownload
     *
     * @param \DateTime $dateDownload
     *
     * @return Download
     */
    public function setDateDownload($dateDownload)
    {
        $this->dateDownload = $dateDownload;

        return $this;
    }

    /**
     * Get dateDownload
     *
     * @return \DateTime
     */
    public function getDateDownload()
    {
        return $this->dateDownload;
    }

    /**
     * Set code
     *
     * @param integer $code
     *
     * @return Download
     */
    public function setCode($code)
    {
        $this->code = $code;

        return $this;
    }

    /**
     * Get code
     *
     * @return integer
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * Set referer
     *
     * @param string $referer
     *
     * @return Download
     */
    public function setReferer($referer)
    {
        $this->referer = $referer;

        return $this;
    }

    /**
     * Get referer
     *
     * @return string
     */
    public function getReferer()
    {
        return $this->referer;
    }

    /**
     * Set asset
     *
     * @param Asset $asset
     *
     * @return Download
     */
    public function setAsset(Asset $asset = null)
    {
        $this->asset = $asset;

        return $this;
    }

    /**
     * Get asset
     *
     * @return Asset
     */
    public function getAsset()
    {
        return $this->asset;
    }

    /**
     * Set ipAddress
     *
     * @param \Mautic\CoreBundle\Entity\IpAddress $ipAddress
     *
     * @return Download
     */
    public function setIpAddress(\Mautic\CoreBundle\Entity\IpAddress $ipAddress)
    {
        $this->ipAddress = $ipAddress;

        return $this;
    }

    /**
     * Get ipAddress
     *
     * @return \Mautic\CoreBundle\Entity\IpAddress
     */
    public function getIpAddress()
    {
        return $this->ipAddress;
    }

    /**
     * Set trackingId
     *
     * @param integer $trackingId
     *
     * @return Download
     */
    public function setTrackingId($trackingId)
    {
        $this->trackingId = $trackingId;

        return $this;
    }

    /**
     * Get trackingId
     *
     * @return integer
     */
    public function getTrackingId()
    {
        return $this->trackingId;
    }

    /**
     * @return mixed
     */
    public function getLead()
    {
        return $this->lead;
    }

    /**
     * @param mixed $lead
     */
    public function setLead($lead)
    {
        $this->lead = $lead;
    }

    /**
     * @return mixed
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * @param mixed $source
     */
    public function setSource($source)
    {
        $this->source = $source;
    }

    /**
     * @return integer
     */
    public function getSourceId()
    {
        return $this->sourceId;
    }

    /**
     * @param mixed $sourceId
     */
    public function setSourceId($sourceId)
    {
        $this->sourceId = (int) $sourceId;
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
}
