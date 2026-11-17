<?php

declare(strict_types=1);

namespace Mautic\UserBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: OidcSubjectIdRepository::class)]
#[ORM\Table(name: self::TABLE_NAME)]
class OidcSubjectId
{
    public const string TABLE_NAME = 'open_id_identifiers';

    public function __construct(
        #[ORM\Id]
        #[ORM\OneToOne(targetEntity: User::class)]
        #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
        private readonly User $user,
        #[ORM\Column(name: 'subject_id', type: 'string', length: 255, unique: true, nullable: true, options: ['collation' => 'utf8mb4_bin'])]
        private ?string $subjectID = null,
    ) {
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getSubjectID(): ?string
    {
        return $this->subjectID;
    }

    public function setSubjectID(?string $subjectID): self
    {
        $this->subjectID = $subjectID;

        return $this;
    }
}
