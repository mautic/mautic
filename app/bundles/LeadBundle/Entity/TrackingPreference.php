<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * A contact's own choice about one tracking purpose. No row means the contact hasn't chosen.
 */
#[ORM\Entity(repositoryClass: TrackingPreferenceRepository::class)]
#[ORM\Table(name: self::TABLE_NAME)]
#[ORM\UniqueConstraint(name: 'unique_lead_purpose', columns: ['lead_id', 'purpose'])]
#[ORM\ChangeTrackingPolicy('DEFERRED_EXPLICIT')]
class TrackingPreference
{
    public const string TABLE_NAME = 'lead_tracking_preferences';

    /**
     * Recording the contact's activity and associating it with their profile.
     */
    public const string PURPOSE_ACTIVITY = 'activity';

    public const string SOURCE_CONTACT_MERGE = 'contact_merge';

    #[ORM\Id]
    #[ORM\Column(type: Types::INTEGER, options: ['unsigned' => true])]
    #[ORM\GeneratedValue]
    private ?int $id = null;

    #[ORM\Column(name: 'date_modified', type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeInterface $dateModified;

    public function __construct(
        #[ORM\ManyToOne(targetEntity: Lead::class)]
        #[ORM\JoinColumn(name: 'lead_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
        private readonly Lead $lead,
        #[ORM\Column(type: Types::STRING, length: 50)]
        private readonly string $purpose,
        #[ORM\Column(type: Types::BOOLEAN)]
        private bool $allowed,
        #[ORM\Column(type: Types::STRING, length: 191, nullable: true)]
        private ?string $source = null,
    ) {
        $this->dateModified = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLead(): Lead
    {
        return $this->lead;
    }

    public function getPurpose(): string
    {
        return $this->purpose;
    }

    public function isAllowed(): bool
    {
        return $this->allowed;
    }

    public function getSource(): ?string
    {
        return $this->source;
    }

    public function getDateModified(): \DateTimeInterface
    {
        return $this->dateModified;
    }

    public function change(bool $allowed, ?string $source = null): void
    {
        $this->allowed      = $allowed;
        $this->source       = $source;
        $this->dateModified = new \DateTimeImmutable();
    }
}
