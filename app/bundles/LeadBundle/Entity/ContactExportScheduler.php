<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Entity;

use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\ClassMetadata;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Mautic\UserBundle\Entity\User;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Mapping\ClassMetadata as ValidatorClassMetadata;

class ContactExportScheduler
{
    private int $id;
    private User $user; // Created by
    private DateTimeImmutable $scheduledDateTime;
    /** @var array<mixed> */
    private array $data;

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

        return $this;
    }

    public function getScheduledDateTime(): ?DateTimeImmutable
    {
        return $this->scheduledDateTime;
    }

    public function setScheduledDateTime(DateTimeImmutable $scheduledDateTime): self
    {
        $this->scheduledDateTime = $scheduledDateTime;

        return $this;
    }

    /**
     * @return array<mixed>|null
     */
    public function getData(): ?array
    {
        return $this->data;
    }

    /**
     * @param array<mixed> $data
     */
    public function setData(array $data): self
    {
        $this->data = $data;

        return $this;
    }
}
