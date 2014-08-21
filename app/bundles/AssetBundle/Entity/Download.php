<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\AssetBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;

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
     * @Serializer\Expose
     * @Serializer\Since("1.0")
     * @Serializer\Groups({"full"})
     */
    private $id;

    /**
     * @ORM\Column(name="date_download", type="datetime")
     * @Serializer\Expose
     * @Serializer\Since("1.0")
     * @Serializer\Groups({"full"})
     */
    private $dateDownload;

    /**
     * @ORM\ManyToOne(targetEntity="Asset")
     * @ORM\JoinColumn(name="asset_id", referencedColumnName="id", nullable=true)
     * @Serializer\Expose
     * @Serializer\Since("1.0")
     * @Serializer\Groups({"full"})
     */
    private $asset;

    /**
     * @ORM\ManyToOne(targetEntity="Mautic\CoreBundle\Entity\IpAddress", cascade={"merge", "persist", "refresh", "detach"})
     * @ORM\JoinColumn(name="ip_id", referencedColumnName="id", nullable=false)
     * @Serializer\Expose
     * @Serializer\Since("1.0")
     * @Serializer\Groups({"full"})
     */
    private $ipAddress;

    /**
     * @ORM\Column(type="integer")
     * @Serializer\Expose
     * @Serializer\Since("1.0")
     * @Serializer\Groups({"full"})
     */
    private $code;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @Serializer\Expose
     * @Serializer\Since("1.0")
     * @Serializer\Groups({"full"})
     */
    private $referer;

    /**
     * @ORM\Column(type="string", name="tracking_id")
     **/
    private $trackingId;

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
     * @return Hit
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
     * @param \Mautic\AssetBundle\Entity\Asset $asset
     * @return Download
     */
    public function setAsset(\Mautic\AssetBundle\Entity\Asset $asset = null)
    {
        $this->asset = $asset;

        return $this;
    }

    /**
     * Get asset
     *
     * @return \Mautic\AssetBundle\Entity\Asset
     */
    public function getAsset()
    {
        return $this->asset;
    }

    /**
     * Set ipAddress
     *
     * @param \Mautic\CoreBundle\Entity\IpAddress $ipAddress
     * @return Hit
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
     * @return integer
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
}
