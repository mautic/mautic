<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MergeRecordRepository::class)]
#[ORM\Table(name: 'contact_merge_records')]
#[ORM\Index(columns: ['date_added'], name: 'contact_merge_date_added')]
#[ORM\Index(columns: ['merged_id'], name: 'contact_merge_ids')]
#[ORM\ChangeTrackingPolicy('DEFERRED_EXPLICIT')]
class MergeRecord
{
    /**
     * @var int
     */
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    private $id;

    #[ORM\ManyToOne(targetEntity: \Mautic\LeadBundle\Entity\Lead::class)]
    #[ORM\JoinColumn(name: 'contact_id', nullable: false, onDelete: 'CASCADE')]
    private ?\Mautic\LeadBundle\Entity\Lead $contact = null;

    #[ORM\Column(name: 'date_added', type: 'datetime')]
    private ?\DateTime $dateAdded = null;

    /**
     * @var string
     */
    #[ORM\Column(type: 'string', length: 191)]
    private $name;

    #[ORM\Column(name: 'merged_id', type: 'integer')]
    private ?int $mergedId = null;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    public function getContact(): ?\Mautic\LeadBundle\Entity\Lead
    {
        return $this->contact;
    }

    public function setContact(Lead $contact): static
    {
        $this->contact = $contact;

        return $this;
    }

    public function getDateAdded(): ?\DateTime
    {
        return $this->dateAdded;
    }

    public function setDateAdded(?\DateTime $dateAdded = null): static
    {
        $dateAdded ??= new \DateTime();

        $this->dateAdded = $dateAdded;

        return $this;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getMergedId(): ?int
    {
        return $this->mergedId;
    }

    /**
     * @param int $mergedId
     */
    public function setMergedId($mergedId): static
    {
        $this->mergedId = (int) $mergedId;

        return $this;
    }
}
