<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Mautic\ApiBundle\Serializer\Driver\ApiMetadataDriver;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;

#[ORM\Entity(repositoryClass: LeadRepository::class)]
#[ORM\Table(name: 'campaign_leads')]
#[ORM\Index(columns: ['date_added'], name: 'campaign_leads_date_added')]
#[ORM\Index(columns: ['date_last_exited'], name: 'campaign_leads_date_exited')]
#[ORM\Index(columns: ['campaign_id', 'manually_removed', 'lead_id', 'rotation'], name: 'campaign_leads')]
#[ORM\ChangeTrackingPolicy('DEFERRED_EXPLICIT')]
class Lead
{
    /**
     * @var Campaign
     */
    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: Campaign::class, inversedBy: 'leads')]
    #[ORM\JoinColumn(name: 'campaign_id', nullable: false, onDelete: 'CASCADE')]
    private $campaign;

    /**
     * @var \Mautic\LeadBundle\Entity\Lead
     */
    private $lead;

    /**
     * @var \DateTimeInterface
     */
    private $dateAdded;

    /**
     * @var \DateTimeInterface
     */
    private $dateLastExited;

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

    /**
     * @var int
     */
    #[ORM\Column(type: 'integer')]
    private $rotation = 1;

    public static function loadMetadata(ORM\ClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->addLead(false, 'CASCADE', true);

        $builder->addDateAdded();

        $builder->addNamedField('dateLastExited', 'datetime', 'date_last_exited', true);
    }

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

    /**
     * @return \Mautic\LeadBundle\Entity\Lead
     */
    public function getLead()
    {
        return $this->lead;
    }

    public function setLead(\Mautic\LeadBundle\Entity\Lead $lead): void
    {
        $this->lead = $lead;
    }

    /**
     * @return Campaign
     */
    public function getCampaign()
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

    /**
     * @return int
     */
    public function getRotation()
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

    /**
     * @return \DateTimeInterface|null
     */
    public function getDateLastExited()
    {
        return $this->dateLastExited;
    }

    public function setDateLastExited(?\DateTime $dateLastExited = null): static
    {
        $this->dateLastExited = $dateLastExited;

        return $this;
    }
}
