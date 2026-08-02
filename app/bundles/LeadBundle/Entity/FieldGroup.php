<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Mautic\ApiBundle\Serializer\Driver\ApiMetadataDriver;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Mautic\CoreBundle\Entity\FormEntity;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Mapping\ClassMetadata;

class FieldGroup extends FormEntity
{
    public const TABLE_NAME  = 'lead_field_groups';

    public const ENTITY_NAME = 'lead_field_group';

    /**
     * Default groups for lead object.
     */
    public const DEFAULT_LEAD_GROUPS = ['core', 'social', 'personal', 'professional'];

    /**
     * Default groups for company object.
     */
    public const DEFAULT_COMPANY_GROUPS = ['core', 'professional', 'other'];

    private ?int $id = null;

    private ?string $name = null;

    private ?string $alias = null;

    private ?string $description = null;

    private int $order = 0;

    /**
     * @param ORM\ClassMetadata<FieldGroup> $metadata
     */
    public static function loadMetadata(ORM\ClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable(self::TABLE_NAME)
            ->setCustomRepositoryClass(FieldGroupRepository::class);

        $builder->addIdColumns();

        $builder->createField('alias', 'string')
            ->columnName('alias')
            ->length(191)
            ->nullable(false)
            ->build();

        $builder->createField('order', 'integer')
            ->columnName('field_order')
            ->option('default', 0)
            ->build();
    }

    public static function loadValidatorMetadata(ClassMetadata $metadata): void
    {
        $metadata->addPropertyConstraint('name', new Assert\NotBlank(message: 'mautic.core.name.required'));

        $metadata->addPropertyConstraint('name', new Assert\Regex(pattern: '/^[\p{L}\p{N}\p{S}\s]+$/u', match: true, message: 'mautic.lead.field_group.name.help'));

        // The alias (auto-generated from the name) is unique in the DB; validate
        // it here so a duplicate or near-duplicate name returns a form error on
        // the name field instead of an unhandled UniqueConstraintViolationException.
        $metadata->addConstraint(new UniqueEntity(fields: ['alias'], errorPath: 'name', message: 'mautic.lead.field_group.name.unique'));
    }

    public static function loadApiMetadata(ApiMetadataDriver $metadata): void
    {
        $metadata->setGroupPrefix('fieldGroup')
            ->addListProperties(['id', 'name', 'alias', 'description', 'order'])
            ->build();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): self
    {
        $this->isChanged('name', $name);
        $this->name = $name;

        return $this;
    }

    public function getAlias(): ?string
    {
        return $this->alias;
    }

    public function setAlias(?string $alias): self
    {
        $this->isChanged('alias', $alias);
        $this->alias = $alias;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->isChanged('description', $description);
        $this->description = $description;

        return $this;
    }

    public function getOrder(): int
    {
        return $this->order;
    }

    public function setOrder(?int $order): self
    {
        $order = (int) $order;
        $this->isChanged('order', $order);
        $this->order = $order;

        return $this;
    }
}
