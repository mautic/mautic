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

    /**
     * @param ORM\ClassMetadata $metadata
     */
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
     * @param string $product
     */
    public function setProduct($product)
    {
        $this->product = $product;
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
    public function getEventDesc()
    {
        return $this->eventDesc;
    }

    /**
     * @param string $eventName
     */
    public function setEventName($eventName)
    {
        $this->eventName = $eventName;
    }

    /**
     * @return \DateTime
     */
    public function getEventDate()
    {
        return $this->eventDate;
    }

    /**
     * @param \DateTime $eventDate
     */
    public function setEventDate(\DateTime $eventDate)
    {
        $this->eventDate = $eventDate;
    }

    /**
     * @return string
     */
    public function getEventType()
    {
        return $this->eventType;
    }

    /**
     * @param string $eventType
     */
    public function setEventType($eventType)
    {
        $this->eventType = $eventType;
    }

    /**
     * @param mixed $eventDesc
     */
    public function setEventDesc($eventDesc)
    {
        $this->eventDesc = $eventDesc;
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
     * @return CitrixEvent
     */
    public function setLead(Lead $lead)
    {
        $this->lead = $lead;

        return $this;
    }
}
