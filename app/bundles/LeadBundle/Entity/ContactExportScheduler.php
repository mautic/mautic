<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\ClassMetadata;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Mautic\UserBundle\Entity\User;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Mapping\ClassMetadata as ValidatorClassMetadata;

class ContactExportScheduler
{
    private ?int $id = null;

    private ?User $user = null; // Created by

    private \DateTimeImmutable $scheduledDateTime;

    /**
     * @var array<mixed>
     */
    private array $data = [];

    /**
     * @var array<mixed>
     */
    private array $changes = [];

    /**
     * @template T of ClassMetadata
     *
     * @param T $metadata
     */
    public static function loadMetadata(ClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);
        $builder->setTable('contact_export_scheduler');
        $builder->setCustomRepositoryClass(ContactExportSchedulerRepository::class);
        $builder->addId();
        $builder->createManyToOne('user', User::class)
            ->addJoinColumn('user_id', 'id', true, false, 'CASCADE')
            ->build();
        $builder->createField('scheduledDateTime', Types::DATETIME_IMMUTABLE)
            ->columnName('scheduled_datetime')
            ->build();
        $builder->addNullableField('data', Types::ARRAY);
    }

    public static function loadValidatorMetadata(ValidatorClassMetadata $metadata): void
    {
        $metadata->addPropertyConstraint(
            'scheduledDate',
            new Assert\NotBlank(
                ['message' => 'mautic.lead.import.dir.notblank']
            )
        );
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;
        $this->addChange('user', $user->getId());

        return $this;
    }

    public function getScheduledDateTime(): ?\DateTimeImmutable
    {
        return $this->scheduledDateTime;
    }

    public function setScheduledDateTime(\DateTimeImmutable $scheduledDateTime): self
    {
        $this->scheduledDateTime = $scheduledDateTime;
        $this->addChange('scheduledDateTime', $scheduledDateTime);

        return $this;
    }

    /**
     * @return array<mixed>
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * @param array<mixed> $data
     */
    public function setData(array $data): self
    {
        $this->data = $data;
        $this->addChange('data', $data);

        return $this;
    }

    /**
     * @return array<mixed>
     */
    public function getChanges(): array
    {
        return $this->changes;
    }

    /**
     * @param mixed $value
     */
    private function addChange(string $property, $value): void
    {
        $this->changes[$property] = $value;
    }
}
