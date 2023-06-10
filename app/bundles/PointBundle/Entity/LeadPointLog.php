<?php

namespace Mautic\PointBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;

class LeadPointLog
{
    /**
     * @var Point
     **/
    private $point;

    /**
     * @var \Mautic\LeadBundle\Entity\Lead
     */
    private $lead;

    /**
     * @var \Mautic\CoreBundle\Entity\IpAddress|null
     */
    private $ipAddress;

    /**
     * @var \DateTimeInterface
     **/
    private $dateFired;

    public static function loadMetadata(ORM\ClassMetadata $metadata)
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable('point_lead_action_log')
            ->setCustomRepositoryClass('Mautic\PointBundle\Entity\LeadPointLogRepository');

        $builder->createManyToOne('point', 'Point')
            ->isPrimaryKey()
            ->addJoinColumn('point_id', 'id', true, false, 'CASCADE')
            ->inversedBy('log')
            ->build();

        $builder->addLead(false, 'CASCADE', true);

        $builder->addIpAddress(true);

        $builder->createField('dateFired', 'datetime')
            ->columnName('date_fired')
            ->build();
    }

    /**
     * @return mixed
     */
    public function getDateFired()
    {
        return $this->dateFired;
    }

    public function setDateFired(mixed $dateFired)
    {
        $this->dateFired = $dateFired;
    }

    /**
     * @return mixed
     */
    public function getIpAddress()
    {
        return $this->ipAddress;
    }

    public function setIpAddress(mixed $ipAddress)
    {
        $this->ipAddress = $ipAddress;
    }

    /**
     * @return mixed
     */
    public function getLead()
    {
        return $this->lead;
    }

    public function setLead(mixed $lead)
    {
        $this->lead = $lead;
    }

    /**
     * @return mixed
     */
    public function getPoint()
    {
        return $this->point;
    }

    public function setPoint(mixed $point)
    {
        $this->point = $point;
    }
}
