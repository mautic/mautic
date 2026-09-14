<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;

#[ORM\Entity(repositoryClass: ListLeadRepository::class)]
#[ORM\Table(name: 'lead_lists_leads')]
#[ORM\Index(columns: ['manually_removed'], name: 'manually_removed')]
#[ORM\Index(columns: ['lead_id', 'leadlist_id', 'manually_removed'], name: 'lead_id_lists_id_removed')]
#[ORM\ChangeTrackingPolicy('DEFERRED_EXPLICIT')]
class ListLead
{
    /**
     * @var string
     */
    public const TABLE_NAME = 'lead_lists_leads';

    /**
     * @var LeadList
     */
    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: LeadList::class, inversedBy: 'leads')]
    #[ORM\JoinColumn(name: 'leadlist_id', nullable: false, onDelete: 'CASCADE')]
    private $list;

    /**
     * @var Lead
     */
    private $lead;

    /**
     * @var \DateTimeInterface
     */
    private $dateAdded;

    /**
     * @var bool
     */
    #[ORM\Column(name: 'manually_removed', type: 'boolean')]
    private $manuallyRemoved = false;

    /**
     * @var bool
     */
    #[ORM\Column(name: 'manually_added', type: 'boolean')]
    private $manuallyAdded = false;

    public static function loadMetadata(ORM\ClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->addLead(false, 'CASCADE', true);

        $builder->addDateAdded();
    }

    /**
     * @return \DateTimeInterface
     */
    public function getDateAdded()
    {
        return $this->dateAdded;
    }

    /**
     * @param \DateTime $date
     */
    public function setDateAdded($date): void
    {
        $this->dateAdded = $date;
    }

    /**
     * @return Lead
     */
    public function getLead()
    {
        return $this->lead;
    }

    /**
     * @param mixed $lead
     */
    public function setLead($lead): void
    {
        $this->lead = $lead;
    }

    /**
     * @return LeadList
     */
    public function getList()
    {
        return $this->list;
    }

    /**
     * @param LeadList $leadList
     */
    public function setList($leadList): void
    {
        $this->list = $leadList;
    }

    /**
     * @return bool
     */
    public function getManuallyRemoved()
    {
        return $this->manuallyRemoved;
    }

    /**
     * @param bool $manuallyRemoved
     */
    public function setManuallyRemoved($manuallyRemoved): void
    {
        $this->manuallyRemoved = $manuallyRemoved;
    }

    /**
     * @return bool
     */
    public function wasManuallyRemoved()
    {
        return $this->manuallyRemoved;
    }

    /**
     * @return bool
     */
    public function getManuallyAdded()
    {
        return $this->manuallyAdded;
    }

    /**
     * @param bool $manuallyAdded
     */
    public function setManuallyAdded($manuallyAdded): void
    {
        $this->manuallyAdded = $manuallyAdded;
    }

    /**
     * @return bool
     */
    public function wasManuallyAdded()
    {
        return $this->manuallyAdded;
    }
}
