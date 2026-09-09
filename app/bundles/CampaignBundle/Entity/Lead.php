<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Mautic\ApiBundle\Serializer\Driver\ApiMetadataDriver;

#[ORM\Entity(repositoryClass: LeadRepository::class)]
#[ORM\Table(name: 'campaign_leads')]
#[ORM\Index(columns: ['date_added'], name: 'campaign_leads_date_added')]
#[ORM\Index(columns: ['date_last_exited'], name: 'campaign_leads_date_exited')]
#[ORM\Index(columns: ['campaign_id', 'manually_removed', 'lead_id', 'rotation'], name: 'campaign_leads')]
#[ORM\ChangeTrackingPolicy('DEFERRED_EXPLICIT')]
class Lead
{
    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: Campaign::class, inversedBy: 'leads')]
    #[ORM\JoinColumn(name: 'campaign_id', nullable: false, onDelete: 'CASCADE')]
    private ?\Mautic\CampaignBundle\Entity\Campaign $campaign = null;

    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: \Mautic\LeadBundle\Entity\Lead::class)]
    #[ORM\JoinColumn(name: 'lead_id', nullable: false, onDelete: 'CASCADE')]
    private ?\Mautic\LeadBundle\Entity\Lead $lead = null;

    /**
     * @var \DateTimeInterface
     */
    #[ORM\Column(name: 'date_added', type: 'datetime')]
    private $dateAdded;

    #[ORM\Column(name: 'date_last_exited', type: 'datetime', nullable: true)]
    private ?\DateTime $dateLastExited = null;

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

    #[ORM\Column(type: 'integer')]
    private int $rotation = 1;

    /**
     * Prepares the metadata for API usage.
     */
    public static function loadApiMetadata(ApiMetadataDriver $metadata): void
    {
        $metadata->setGroupPrefix('campaignLead')
            ->addListProperties(
                [
                    'dateAdded',
                    'manuallyRemoved',
                    'manuallyAdded',
                    'rotation',
                    'dateLastExited',
                ]
            )
            ->addProperties(
                [
                    'lead',
                    'campaign',
                ]
            )
            ->build();
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

    public function getLead(): ?\Mautic\LeadBundle\Entity\Lead
    {
        return $this->lead;
    }

    public function setLead(\Mautic\LeadBundle\Entity\Lead $lead): void
    {
        $this->lead = $lead;
    }

    public function getCampaign(): ?\Mautic\CampaignBundle\Entity\Campaign
    {
        return $this->campaign;
    }

    public function setCampaign(Campaign $campaign): void
    {
        $this->campaign = $campaign;
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

    public function getRotation(): int
    {
        return $this->rotation;
    }

    /**
     * @param int $rotation
     */
    public function setRotation($rotation): static
    {
        $this->rotation = (int) $rotation;

        return $this;
    }

    public function startNewRotation(): static
    {
        ++$this->rotation;
        $this->dateAdded = new \DateTime();

        return $this;
    }

    public function getDateLastExited(): ?\DateTime
    {
        return $this->dateLastExited;
    }

    public function setDateLastExited(?\DateTime $dateLastExited = null): static
    {
        $this->dateLastExited = $dateLastExited;

        return $this;
    }
}
