<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MergeRecordRepository::class)]
#[ORM\Table(name: 'contact_merge_records')]
#[ORM\Index(name: 'contact_merge_date_added', columns: ['date_added'])]
#[ORM\Index(name: 'contact_merge_ids', columns: ['merged_id'])]
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

    /**
     * @var Lead
     */
    #[ORM\ManyToOne(targetEntity: Lead::class)]
    #[ORM\JoinColumn(name: 'contact_id', nullable: false, onDelete: 'CASCADE')]
    private $contact;

    /**
     * @var \DateTimeInterface
     */
    #[ORM\Column(name: 'date_added', type: 'datetime')]
    private $dateAdded;

    /**
     * @var string
     */
    #[ORM\Column(type: 'string', length: 191)]
    private $name;

    /**
     * @var int
     */
    #[ORM\Column(name: 'merged_id', type: 'integer')]
    private $mergedId;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return Lead
     */
    public function getContact()
    {
        return $this->contact;
    }

    public function setContact(Lead $contact): static
    {
        $this->contact = $contact;

        return $this;
    }

    /**
     * @return \DateTimeInterface
     */
    public function getDateAdded()
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

    /**
     * @return int
     */
    public function getMergedId()
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
