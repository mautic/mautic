<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Mautic\UserBundle\Entity\User;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ContactExportSchedulerRepository::class)]
#[ORM\Table(name: 'contact_export_scheduler')]
#[ORM\ChangeTrackingPolicy('DEFERRED_EXPLICIT')]
class ContactExportScheduler
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer', options: ['unsigned' => true])]
    #[ORM\GeneratedValue]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', onDelete: 'CASCADE')]
    private ?User $user = null; // Created by

    #[Assert\NotBlank()]
    #[ORM\Column(name: 'scheduled_datetime', type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $scheduledDateTime;

    /**
     * @var array<mixed>
     */
    #[ORM\Column(type: Types::ARRAY, nullable: true)]
    private array $data = [];

    /**
     * @var array<mixed>
     */
    private array $changes = [];

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
