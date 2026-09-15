<?php

namespace MauticPlugin\MauticSocialBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;

#[ORM\Table(name: 'monitoring_leads')]
#[ORM\Entity(repositoryClass: LeadRepository::class)]
#[ORM\ChangeTrackingPolicy('DEFERRED_EXPLICIT')]
class Lead
{
    /**
     * @var Monitoring
     */
    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: Monitoring::class)]
    #[ORM\JoinColumn(name: 'monitor_id', nullable: false, onDelete: 'CASCADE')]
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
            ->setCustomRepositoryClass(LeadRepository::class);

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

    public function setDateAdded($dateAdded): static
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

    public function setLead($lead): static
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

    public function setMonitor($monitor): static
    {
        $this->monitor = $monitor;

        return $this;
    }
}
