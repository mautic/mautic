<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticCitrixBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Mautic\LeadBundle\Entity\Lead;

/**
 * @ORM\Table(name="plugin_citrix_events")
 * @ORM\Entity
 */
class CitrixEvent
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var Lead
     */
    protected $lead;

    /**
     * @ORM\Column(name="product", type="string", length=20)
     */
    protected $product;

    /**
     * @ORM\Column(name="email", type="string", length=255)
     */
    protected $email;

    /**
     * @ORM\Column(name="event_name", type="string", length=255)
     */
    protected $eventName;

    /**
     * @ORM\Column(name="event_desc", type="string", length=255)
     */
    protected $eventDesc;

    /**
     * @ORM\Column(name="event_type", type="string", length=50)
     */
    protected $eventType;

    /**
     * @ORM\Column(name="event_date", type="datetime")
     */
    protected $eventDate;

    public function __construct()
    {
        $this->product   = 'undefined';
        $this->email     = 'undefined';
        $this->eventName = 'undefined';
        $this->eventDesc = 'undefined';
        $this->eventDate = new \Datetime();
        $this->eventType = 'undefined';
    }

    public static function loadMetadata(ORM\ClassMetadata $metadata)
    {
        $builder = new ClassMetadataBuilder($metadata);
        $builder->setTable('plugin_citrix_events')
            ->setCustomRepositoryClass('MauticPlugin\MauticCitrixBundle\Entity\CitrixEventRepository')
            ->addIndex(['product', 'email'], 'citrix_event_email')
            ->addIndex(['product', 'event_name', 'event_type'], 'citrix_event_name')
            ->addIndex(['product', 'event_type', 'event_date'], 'citrix_event_type')
            ->addIndex(['product', 'email', 'event_type'], 'citrix_event_product')
            ->addIndex(['product', 'email', 'event_type', 'event_name'], 'citrix_event_product_name')
            ->addIndex(['product', 'event_type', 'event_name', 'lead_id'], 'citrix_event_product_name_lead')
            ->addIndex(['product', 'event_type', 'lead_id'], 'citrix_event_product_type_lead')
            ->addIndex(['event_date'], 'citrix_event_date');
        $builder->addId();
        $builder->addNamedField('product', 'string', 'product');
        $builder->addNamedField('email', 'string', 'email');
        $builder->addNamedField('eventName', 'string', 'event_name');
        $builder->addNamedField('eventDesc', 'string', 'event_desc', true);
        $builder->createField('eventType', 'string')
            ->columnName('event_type')
            ->length(50)
            ->build();
        $builder->addNamedField('eventDate', 'datetime', 'event_date');
        $builder->addLead();
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return get_object_vars($this);
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getProduct()
    {
        return $this->product;
    }

    /**
     * @param $product
     *
     * @return $this
     */
    public function setProduct($product)
    {
        $this->product = $product;

        return $this;
    }

    /**
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @param string $email
     */
    public function setEmail($email)
    {
        $this->email = $email;
    }

    /**
     * @return string
     */
    public function getEventName()
    {
        return $this->eventName;
    }

    /**
     * @return string
     */
    public function getEventNameOnly()
    {
        $eventName = $this->eventName;

        return substr($eventName, 0, strpos($eventName, '_#'));
    }

    /**
     * @return string
     */
    public function getEventId()
    {
        $eventName = $this->eventName;

        return substr($eventName, strpos($eventName, '_#') + 2);
    }

    /**
     * @return string
     */
    public function getEventDesc()
    {
        $pos = strpos($this->eventDesc, '_!');
        if (false === $pos) {
            return $this->eventDesc;
        }

        return substr($this->eventDesc, 0, $pos);
    }

    /**
     * @return string
     */
    public function getJoinUrl()
    {
        $pos = strpos($this->eventDesc, '_!');
        if (false === $pos) {
            return '';
        }

        return substr($this->eventDesc, $pos + 2);
    }

    /**
     * @param $eventName
     *
     * @return $this
     */
    public function setEventName($eventName)
    {
        $this->eventName = $eventName;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getEventDate()
    {
        return $this->eventDate;
    }

    /**
     * @return $this
     */
    public function setEventDate(\DateTime $eventDate)
    {
        $this->eventDate = $eventDate;

        return $this;
    }

    /**
     * @return string
     */
    public function getEventType()
    {
        return $this->eventType;
    }

    /**
     * @param $eventType
     *
     * @return $this
     */
    public function setEventType($eventType)
    {
        $this->eventType = $eventType;

        return $this;
    }

    /**
     * @param $eventDesc
     *
     * @return $this
     */
    public function setEventDesc($eventDesc)
    {
        $this->eventDesc = $eventDesc;

        return $this;
    }

    /**
     * @return Lead
     */
    public function getLead()
    {
        return $this->lead;
    }

    /**
     * @return CitrixEvent
     */
    public function setLead(Lead $lead)
    {
        $this->lead = $lead;

        return $this;
    }
}
