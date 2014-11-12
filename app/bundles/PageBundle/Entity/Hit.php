<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PageBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\EmailBundle\Entity\Email;
use Mautic\LeadBundle\Entity\Lead;

/**
 * Class Hit
 * @ORM\Table(name="page_hits")
 * @ORM\Entity(repositoryClass="Mautic\PageBundle\Entity\HitRepository")
 * @Serializer\ExclusionPolicy("all")
 */
class Hit
{

    /**
     * @ORM\Column(type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\Column(name="date_hit", type="datetime")
     */
    private $dateHit;

    /**
     * @ORM\Column(name="date_left", type="datetime", nullable=true)
     */
    private $dateLeft;

    /**
     * @ORM\ManyToOne(targetEntity="Page")
     * @ORM\JoinColumn(name="page_id", referencedColumnName="id", nullable=true)
     */
    private $page;

    /**
     * @ORM\ManyToOne(targetEntity="Mautic\LeadBundle\Entity\Lead")
     * @ORM\JoinColumn(name="lead_id", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     */
    private $lead;

    /**
     * @ORM\ManyToOne(targetEntity="Mautic\CoreBundle\Entity\IpAddress", cascade={"merge", "persist"})
     * @ORM\JoinColumn(name="ip_id", referencedColumnName="id", nullable=false)
     */
    private $ipAddress;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $country;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $region;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $city;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $isp;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $organization;

    /**
     * @ORM\Column(type="integer")
     */
    private $code;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $referer;

    /**
     * @ORM\Column(type="string")
     */
    private $url;

    /**
     * @ORM\Column(name="user_agent", type="string", nullable=true)
     */
    private $userAgent;

    /**
     * @ORM\Column(name="remote_host", type="string", nullable=true)
     */
    private $remoteHost;

    /**
     * @ORM\Column(name="page_language", type="string", nullable=true)
     */
    private $pageLanguage;

    /**
     * @ORM\Column(name="browser_languages", type="array", nullable=true)
     */
    private $browserLanguages = array();

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
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set dateHit
     *
     * @param \DateTime $dateHit
     * @return Hit
     */
    public function setDateHit($dateHit)
    {
        $this->dateHit = $dateHit;

        return $this;
    }

    /**
     * Get dateHit
     *
     * @return \DateTime
     */
    public function getDateHit()
    {
        return $this->dateHit;
    }

    /**
     * @return mixed
     */
    public function getDateLeft ()
    {
        return $this->dateLeft;
    }

    /**
     * @param mixed $dateLeft
     */
    public function setDateLeft ($dateLeft)
    {
        $this->dateLeft = $dateLeft;
    }

    /**
     * Set country
     *
     * @param string $country
     * @return Hit
     */
    public function setCountry($country)
    {
        $this->country = $country;

        return $this;
    }

    /**
     * Get country
     *
     * @return string
     */
    public function getCountry()
    {
        return $this->country;
    }

    /**
     * Set region
     *
     * @param string $region
     * @return Hit
     */
    public function setRegion($region)
    {
        $this->region = $region;

        return $this;
    }

    /**
     * Get region
     *
     * @return string
     */
    public function getRegion()
    {
        return $this->region;
    }

    /**
     * Set city
     *
     * @param string $city
     * @return Hit
     */
    public function setCity($city)
    {
        $this->city = $city;

        return $this;
    }

    /**
     * Get city
     *
     * @return string
     */
    public function getCity()
    {
        return $this->city;
    }

    /**
     * Set isp
     *
     * @param string $isp
     * @return Hit
     */
    public function setIsp($isp)
    {
        $this->isp = $isp;

        return $this;
    }

    /**
     * Get isp
     *
     * @return string
     */
    public function getIsp()
    {
        return $this->isp;
    }

    /**
     * Set organization
     *
     * @param string $organization
     * @return Hit
     */
    public function setOrganization($organization)
    {
        $this->organization = $organization;

        return $this;
    }

    /**
     * Get organization
     *
     * @return string
     */
    public function getOrganization()
    {
        return $this->organization;
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
     * @return Hit
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
     * Set url
     *
     * @param string $url
     * @return Hit
     */
    public function setUrl($url)
    {
        $this->url = $url;

        return $this;
    }

    /**
     * Get url
     *
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Set userAgent
     *
     * @param string $userAgent
     * @return Hit
     */
    public function setUserAgent($userAgent)
    {
        $this->userAgent = $userAgent;

        return $this;
    }

    /**
     * Get userAgent
     *
     * @return string
     */
    public function getUserAgent()
    {
        return $this->userAgent;
    }

    /**
     * Set remoteHost
     *
     * @param string $remoteHost
     * @return Hit
     */
    public function setRemoteHost($remoteHost)
    {
        $this->remoteHost = $remoteHost;

        return $this;
    }

    /**
     * Get remoteHost
     *
     * @return string
     */
    public function getRemoteHost()
    {
        return $this->remoteHost;
    }

    /**
     * Set page
     *
     * @param \Mautic\PageBundle\Entity\Page $page
     * @return Hit
     */
    public function setPage(\Mautic\PageBundle\Entity\Page $page = null)
    {
        $this->page = $page;

        return $this;
    }

    /**
     * Get page
     *
     * @return \Mautic\PageBundle\Entity\Page
     */
    public function getPage()
    {
        return $this->page;
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
     * @return Page
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
     * Set pageLanguage
     *
     * @param string $pageLanguage
     * @return Hit
     */
    public function setPageLanguage($pageLanguage)
    {
        $this->pageLanguage = $pageLanguage;

        return $this;
    }

    /**
     * Get pageLanguage
     *
     * @return string
     */
    public function getPageLanguage()
    {
        return $this->pageLanguage;
    }

    /**
     * Set browserLanguages
     *
     * @param string $browserLanguages
     * @return Hit
     */
    public function setBrowserLanguages($browserLanguages)
    {
        $this->browserLanguages = $browserLanguages;

        return $this;
    }

    /**
     * Get browserLanguages
     *
     * @return string
     */
    public function getBrowserLanguages()
    {
        return $this->browserLanguages;
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
}
