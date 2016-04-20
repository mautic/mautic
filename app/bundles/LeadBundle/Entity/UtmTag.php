<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Mautic\ApiBundle\Serializer\Driver\ApiMetadataDriver;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Mautic\CoreBundle\Entity\FormEntity;
use Symfony\Component\Validator\Constraints as Assert;


class UtmTag
{

    /**
     * @var int
     */
    private $id;

    /**
     * @var string
     */
    private $name;
    
    /**
     * @var string
     */
    private $type;

    /**
     * @var \DateTime
     */
    private $publishUp;

    /**
     * @var \DateTime
     */
    private $publishDown;


    /**
     * @var string
     */
    private $referrer;

    /**
     * @var \Mautic\LeadBundle\Entity\Lead
     */
    private $lead;

    /**
     * @var \Mautic\CoreBundle\Entity\IpAddress
     */
    private $ipAddress;

    /**
     * @var
     */
    private $url;

    /**
     * @var string
     */
    private $urlTitle;

    /**
     * @var string
     */
    private $userAgent;

    /**
     * @var string
     */
    private $utmTag;

    /**
     * @var string
     */
    private $remoteHost;

    /**
     * @var array
     */
    private $query = array();
    

    /**
     * @param ORM\ClassMetadata $metadata
     */
    public static function loadMetadata (ORM\ClassMetadata $metadata)
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable('tmutmtags')
            ->setCustomRepositoryClass('Mautic\UtmTagBundle\Entity\UtmTagRepository')
            ->addIndex(array('type'), 'tmutmtag_type_search');

        $builder->addId();

        $builder->createField('type', 'string')
            ->length(50)
            ->build();

        $builder->addPublishDates();

        $builder->addField('referrer', 'string');

        $builder->addLead(true, 'SET NULL');

        $builder->createField('url', 'text')
            ->nullable()
            ->build();

        $builder->createField('urlTitle', 'string')
            ->columnName('url_title')
            ->nullable()
            ->build();

        $builder->createField('userAgent', 'text')
            ->columnName('user_agent')
            ->nullable()
            ->build();

        $builder->createField('remoteHost', 'string')
            ->columnName('remote_host')
            ->nullable()
            ->build();

        $builder->addNullableField('query', 'array');
    }


    /**
     * Prepares the metadata for API usage
     *
     * @param $metadata
     */
    public static function loadApiMetadata(ApiMetadataDriver $metadata)
    {
        $metadata->setGroupPrefix('tmutmtag')
            ->addListProperties(
                array(
                    'id',
                    'name',
                    'category',
                    'type',
                    'referrer',
                    'lead',
                    'url',
                    'urlTitle',
                    'userAgent',
                    'remoteHost',
                    'query'
                )
            )
            ->build();
    }

    /**poin
     * Get id
     *
     * @return integer
     */
    public function getId ()
    {
        return $this->id;
    }

    /**
     * Set type
     *
     * @param string $type
     *
     * @return Action
     */
    public function setType ($type)
    {
        $this->isChanged('type', $type);
        $this->type = $type;

        return $this;
    }

    /**
     * Get type
     *
     * @return string
     */
    public function getType ()
    {
        return $this->type;
    }

    /**
     * Set name
     *
     * @param string $name
     *
     * @return ActionDESC
     */
    public function setName ($name)
    {
        $this->isChanged('name', $name);
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName ()
    {
        return $this->name;
    }

    /**
     * Set name
     *
     * @param string $name
     *
     * @return Action
     */
    public function setReferrer ($referrer)
    {
        $this->isChanged('referrer', $referrer);
        $this->referrer = $referrer;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getReferrer ()
    {
        return $this->referrer;
    }

    /**
     * @return Lead
     */
    public function getLead()
    {
        return $this->lead;
    }

    /**
     * @param Lead $lead
     *
     * @return Hit
     */
    public function setLead(Lead $lead)
    {
        $this->lead = $lead;

        return $this;
    }

    /**
     * Set ipAddress
     *
     * @param \Mautic\CoreBundle\Entity\IpAddress $ipAddress
     *
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
     * Set url
     *
     * @param string $url
     *
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
     *
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
     *
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
     * @return array
     */
    public function getQuery()
    {
        return $this->query;
    }

    /**
     * @param array $query
     *
     * @return Hit
     */
    public function setQuery($query)
    {
        $this->query = $query;

        return $this;
    }

    /**
     * Set publishUp
     *
     * @param \DateTime $publishUp
     *
     * @return Point
     */
    public function setPublishUp ($publishUp)
    {
        $this->isChanged('publishUp', $publishUp);
        $this->publishUp = $publishUp;

        return $this;
    }

    /**
     * Get publishUp
     *
     * @return \DateTime
     */
    public function getPublishUp ()
    {
        return $this->publishUp;
    }

    /**
     * Set publishDown
     *
     * @param \DateTime $publishDown
     *
     * @return Point
     */
    public function setPublishDown ($publishDown)
    {
        $this->isChanged('publishDown', $publishDown);
        $this->publishDown = $publishDown;

        return $this;
    }

    /**
     * Get publishDown
     *
     * @return \DateTime
     */
    public function getPublishDown ()
    {
        return $this->publishDown;
    }

    /**
     * Set url title
     *
     * @param string $urlTitle
     *
     * @return Hit
     */
    public function setUrlTitle($urlTitle)
    {
        $this->urlTitle = $urlTitle;

        return $this;
    }

    /**
     * Get url title
     *
     * @return string
     */
    public function getUrlTitle()
    {
        return $this->urlTitle;
    }

}
