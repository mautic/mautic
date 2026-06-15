<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Mautic\ApiBundle\Serializer\Driver\ApiMetadataDriver;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Mautic\CoreBundle\Entity\FormEntity;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Mapping\ClassMetadata;

class FieldGroup extends FormEntity
{
    public const TABLE_NAME  = 'lead_field_groups';
    public const ENTITY_NAME = 'lead_field_group';

    /** Default groups for lead object. */
    public const DEFAULT_LEAD_GROUPS = ['core', 'social', 'personal', 'professional'];

    /** Default groups for company object. */
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
        $metadata->addPropertyConstraint('name', new Assert\NotBlank([
            'message' => 'mautic.core.name.required',
        ]));

        $metadata->addPropertyConstraint('name', new Assert\Regex([
            'pattern' => '/^[a-zA-Z0-9\s]+$/',
            'match'   => true,
            'message' => 'mautic.lead.field_group.name.help',
        ]));
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

        if (null === $this->alias && null !== $name) {
            $this->alias = self::slugify($name);
        }

        return $this;
    }

    public function getAlias(): ?string
    {
        return $this->alias;
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

    public static function slugify(string $name): string
    {
        $slug = strtolower(trim($name));
        $slug = preg_replace('/\s+/', '_', $slug) ?? $slug;
        $slug = preg_replace('/[^a-z0-9_]/', '', $slug) ?? $slug;

        return $slug;
    }
}
