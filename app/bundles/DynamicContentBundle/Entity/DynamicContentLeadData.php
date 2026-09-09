<?php

namespace Mautic\DynamicContentBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Entity\CommonEntity;
use Mautic\LeadBundle\Entity\Lead;

#[ORM\Entity(repositoryClass: DynamicContentLeadDataRepository::class)]
#[ORM\Table(name: 'dynamic_content_lead_data')]
#[ORM\ChangeTrackingPolicy('DEFERRED_EXPLICIT')]
class DynamicContentLeadData extends CommonEntity
{
    /**
     * @var int
     */
    #[ORM\Id]
    #[ORM\Column(type: 'integer', options: ['unsigned' => true])]
    #[ORM\GeneratedValue]
    private $id;

    /**
     * @var \DateTimeInterface
     */
    #[ORM\Column(name: 'date_added', type: 'datetime', nullable: true)]
    private $dateAdded;

    /**
     * @var DynamicContent|null
     */
    #[ORM\ManyToOne(targetEntity: 'DynamicContent', inversedBy: 'id')]
    #[ORM\JoinColumn(name: 'dynamic_content_id', onDelete: 'CASCADE')]
    private $dynamicContent;

    /**
     * @var Lead
     */
    #[ORM\ManyToOne(targetEntity: \Mautic\LeadBundle\Entity\Lead::class)]
    #[ORM\JoinColumn(name: 'lead_id', nullable: false, onDelete: 'CASCADE')]
    private $lead;

    /**
     * @var \DateTimeInterface
     */
    private $dataAdded;

    /**
     * @var string
     */
    #[ORM\Column(type: 'text')]
    private $slot;

    /**
     * @return int|null
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return \DateTimeInterface|null
     */
    public function getDateAdded()
    {
        return $this->dateAdded;
    }

    /**
     * @param \DateTime $dateAdded
     */
    public function setDateAdded($dateAdded): static
    {
        $this->dateAdded = $dateAdded;

        return $this;
    }

    /**
     * @return DynamicContent|null
     */
    public function getDynamicContent()
    {
        return $this->dynamicContent;
    }

    /**
     * @param DynamicContent $dynamicContent
     */
    public function setDynamicContent($dynamicContent): static
    {
        $this->dynamicContent = $dynamicContent;

        return $this;
    }

    /**
     * @return Lead|null
     */
    public function getLead()
    {
        return $this->lead;
    }

    /**
     * @param Lead $lead
     */
    public function setLead($lead): static
    {
        $this->lead = $lead;

        return $this;
    }

    /**
     * @return \DateTimeInterface|null
     */
    public function getDataAdded()
    {
        return $this->dataAdded;
    }

    /**
     * @param \DateTime $dataAdded
     */
    public function setDataAdded($dataAdded): static
    {
        $this->dataAdded = $dataAdded;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getSlot()
    {
        return $this->slot;
    }

    /**
     * @param string $slot
     */
    public function setSlot($slot): static
    {
        $this->slot = $slot;

        return $this;
    }
}
