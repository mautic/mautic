<?php

namespace MauticPlugin\MauticSocialBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;

#[ORM\Table(name: 'monitoring_leads')]
#[ORM\Entity(repositoryClass: LeadRepository::class)]
class Lead
{
    /**
     * @var Monitoring
     */
    private $monitor;

    /**
     * @var \Mautic\LeadBundle\Entity\Lead
     */
    private $lead;

    /**
     * @var \DateTimeInterface
     */
    private $dateAdded;

    public static function loadMetadata(ORM\ClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable('monitoring_leads')
            ->setCustomRepositoryClass(\MauticPlugin\MauticSocialBundle\Entity\LeadRepository::class);

        $builder->createManyToOne('monitor', 'Monitoring')
            ->isPrimaryKey()
            ->addJoinColumn('monitor_id', 'id', false, false, 'CASCADE')
            ->build();

        $builder->addLead(false, 'CASCADE', true);

        $builder->addNamedField('dateAdded', 'datetime', 'date_added');
    }

    /**
     * @return mixed
     */
    public function getDateAdded()
    {
        return $this->dateAdded;
    }

    /**
     * @return $this
     */
    public function setDateAdded($dateAdded)
    {
        $this->dateAdded = $dateAdded;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getLead()
    {
        return $this->lead;
    }

    /**
     * @return $this
     */
    public function setLead($lead)
    {
        $this->lead = $lead;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getMonitor()
    {
        return $this->monitor;
    }

    /**
     * @return $this
     */
    public function setMonitor($monitor)
    {
        $this->monitor = $monitor;

        return $this;
    }
}
