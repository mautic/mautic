<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;

/**
 * A contact's own choice about one tracking purpose. No row means the contact hasn't chosen.
 */
class TrackingPreference
{
    public const TABLE_NAME = 'lead_tracking_preferences';

    /**
     * Recording the contact's activity and associating it with their profile.
     */
    public const PURPOSE_ACTIVITY = 'activity';

    public const SOURCE_CONTACT_MERGE = 'contact_merge';

    private ?int $id = null;

    private \DateTimeInterface $dateModified;

    public function __construct(
        private readonly Lead $lead,
        private readonly string $purpose,
        private bool $allowed,
        private ?string $source = null,
    ) {
        $this->dateModified = new \DateTimeImmutable();
    }

    public static function loadMetadata(ORM\ClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable(self::TABLE_NAME)
            ->setCustomRepositoryClass(TrackingPreferenceRepository::class)
            ->addUniqueConstraint(['lead_id', 'purpose'], 'unique_lead_purpose');

        $builder->addId();

        $builder->addLead(false, 'CASCADE');

        $builder->createField('purpose', Types::STRING)
            ->length(50)
            ->build();

        $builder->addField('allowed', Types::BOOLEAN);

        $builder->createField('source', Types::STRING)
            ->length(191)
            ->nullable()
            ->build();

        $builder->addNamedField('dateModified', Types::DATETIME_IMMUTABLE, 'date_modified');
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
