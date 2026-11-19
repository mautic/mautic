<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;

/**
 * One copy of a sent email that its recipient asked Mautic to send to somebody else.
 *
 * Only the sent email (its stat) and the time are kept. Nothing about the person
 * who received the copy is stored.
 */
class EmailForward
{
    public const TABLE_NAME = 'email_forwards';

    private ?int $id = null;

    private readonly \DateTimeInterface $dateSent;

    public function __construct(
        private readonly Stat $stat,
    ) {
        $this->dateSent = new \DateTimeImmutable();
    }

    public static function loadMetadata(ORM\ClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable(self::TABLE_NAME)
            ->setCustomRepositoryClass(EmailForwardRepository::class);

        $builder->addId();

        $builder->createManyToOne('stat', Stat::class)
            ->addJoinColumn('stat_id', 'id', false, false, 'CASCADE')
            ->build();

        $builder->addNamedField('dateSent', Types::DATETIME_IMMUTABLE, 'date_sent');
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStat(): Stat
    {
        return $this->stat;
    }

    public function getDateSent(): \DateTimeInterface
    {
        return $this->dateSent;
    }
}
