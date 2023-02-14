<?php

declare(strict_types=1);

namespace Mautic\OpenIdBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Mautic\OpenIdBundle\Repository\SubjectIdRepository;
use Mautic\UserBundle\Entity\User;

class SubjectId
{
    private User $user;

    private ?string $subjectID = null;

    public const TABLE_NAME = 'open_id_identifiers';

    public static function loadMetadata(ORM\ClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable(self::TABLE_NAME)
            ->setCustomRepositoryClass(SubjectIdRepository::class);

        $builder->createOneToOne('user', User::class)
            ->addJoinColumn('user_id', 'id', false, false, 'CASCADE')
            ->makePrimaryKey()
            ->build();

        $builder->createField('subjectID', 'string')
            ->columnName('subject_id')
            ->length(255)
            ->nullable()
            ->unique()
            ->build();
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;

        return $this;
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
