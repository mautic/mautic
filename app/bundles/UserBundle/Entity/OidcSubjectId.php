<?php

declare(strict_types=1);

namespace Mautic\UserBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;

class OidcSubjectId
{
    public const TABLE_NAME = 'open_id_identifiers';

    public function __construct(private readonly User $user, private ?string $subjectID = null)
    {
    }

    public static function loadMetadata(ORM\ClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable(self::TABLE_NAME)
            ->setCustomRepositoryClass(OidcSubjectIdRepository::class);

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
