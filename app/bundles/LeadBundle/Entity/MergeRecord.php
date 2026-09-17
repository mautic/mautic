<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;

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

    /**
     * @var Lead
     */
    private $contact;

    /**
     * @var \DateTimeInterface
     */
    private $dateAdded;

    /**
     * @var string
     */
    private $name;

    /**
     * @var int
     */
    private $mergedId;

    public static function loadMetadata(ORM\ClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->addContact()
            ->addDateAdded()
            ->addNamedField('mergedId', 'integer', 'merged_id')
            ->addField('name', 'string');
    }

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
