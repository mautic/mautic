<?php

declare(strict_types=1);

namespace Mautic\FormBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;

/**
 * How many times a form was submitted in an hour. These totals are aggregate analytics: they only ever grow,
 * they stay when the submissions themselves are deleted, and they are not linked to any contact.
 *
 * Not final because Doctrine generates a proxy for every entity.
 */
class SubmissionTotal
{
    public const TABLE_NAME = 'form_submission_totals';

    private ?int $id = null;

    private int $total = 0;

    public function __construct(
        private readonly Form $form,
        private readonly \DateTimeImmutable $hour,
    ) {
    }

    public static function loadMetadata(ORM\ClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable(self::TABLE_NAME)
            ->setCustomRepositoryClass(SubmissionTotalRepository::class)
            ->addUniqueConstraint(['form_id', 'hour'], 'form_submission_totals_form_hour');

        $builder->addId();

        $builder->createManyToOne('form', Form::class)
            ->addJoinColumn('form_id', 'id', false, false, 'CASCADE')
            ->build();

        $builder->addNamedField('hour', Types::DATETIME_IMMUTABLE, 'hour');
        $builder->addNamedField('total', Types::INTEGER, 'total');
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getForm(): Form
    {
        return $this->form;
    }

    /**
     * The start of the hour the submissions fall in, in UTC.
     */
    public function getHour(): \DateTimeImmutable
    {
        return $this->hour;
    }

    public function getTotal(): int
    {
        return $this->total;
    }
}
