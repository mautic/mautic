<?php

namespace Mautic\FormBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Mautic\ApiBundle\Serializer\Driver\ApiMetadataDriver;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Mautic\CoreBundle\Entity\UuidInterface;
use Mautic\CoreBundle\Entity\UuidTrait;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Mapping\ClassMetadata;

/**
 * @ApiResource(
 *   attributes={
 *     "security"="false",
 *     "normalization_context"={
 *       "groups"={
 *         "action:read"
 *        },
 *       "swagger_definition_name"="Read"
 *     },
 *     "denormalization_context"={
 *       "groups"={
 *         "action:write"
 *       },
 *       "swagger_definition_name"="Write"
 *     }
 *   }
 * )
 */
class Action implements UuidInterface
{
    use UuidTrait;

    /**
     * @var int
     */
    private $id;

    /**
     * @var string
     */
    private $name;

    /**
     * @var string|null
     */
    private $description;

    /**
     * @var string
     */
    private $type;

    /**
     * @var int
     */
    private $order = 0;

    /**
     * @var array
     */
    private $properties = [];

    /**
     * @var Form|null
     */
    private $form;

    /**
     * @var array
     */
    private $changes;

    /**
     * Reset properties on clone.
     */
    public function __clone()
    {
        $this->id   = null;
        $this->form = null;
    }

    public static function loadMetadata(ORM\ClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable('form_actions')
            ->setCustomRepositoryClass(ActionRepository::class)
            ->addIndex(['type'], 'form_action_type_search');

        $builder->addIdColumns();

        $builder->createField('type', 'string')
            ->length(50)
            ->build();

        $builder->createField('order', 'integer')
            ->columnName('action_order')
            ->build();

        $builder->addField('properties', 'array');

        $builder->createManyToOne('form', 'Form')
            ->inversedBy('actions')
            ->addJoinColumn('form_id', 'id', false, false, 'CASCADE')
            ->build();

        static::addUuidField($builder);
    }

    /**
     * Prepares the metadata for API usage.
     */
    public static function loadApiMetadata(ApiMetadataDriver $metadata): void
    {
        $metadata->setGroupPrefix('form')
            ->addProperties(
                [
                    'id',
                    'name',
                    'description',
                    'type',
                    'order',
                    'properties',
                ]
            )
            ->build();
    }

    public static function loadValidatorMetadata(ClassMetadata $metadata): void
    {
        $metadata->addPropertyConstraint('type', new Assert\NotBlank([
            'message' => 'mautic.core.name.required',
            'groups'  => ['action'],
        ]));
    }

    private function isChanged($prop, $val): void
    {
        if ($this->$prop != $val) {
            $this->changes[$prop] = [$this->$prop, $val];
        }
    }

    /**
     * @return array
     */
    public function getChanges()
    {
        return $this->changes;
    }

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set order.
     *
     * @param int $order
     *
     * @return Action
     */
    public function setOrder($order)
    {
        $this->isChanged('order', $order);

        $this->order = $order;

        return $this;
    }

    /**
     * Get order.
     *
     * @return int
     */
    public function getOrder()
    {
        return $this->order;
    }

    /**
     * Set properties.
     *
     * @param array $properties
     *
     * @return Action
     */
    public function setProperties($properties)
    {
        $this->isChanged('properties', $properties);

        $this->properties = $properties;

        return $this;
    }

    /**
     * Get properties.
     *
     * @return array
     */
    public function getProperties()
    {
        return $this->properties;
    }

    /**
     * Set form.
     *
     * @return Action
     */
    public function setForm(Form $form)
    {
        $this->form = $form;

        return $this;
    }

    /**
     * Get form.
     *
     * @return Form|null
     */
    public function getForm()
    {
        return $this->form;
    }

    /**
     * Set type.
     *
     * @param string $type
     *
     * @return Action
     */
    public function setType($type)
    {
        $this->isChanged('type', $type);
        $this->type = $type;

        return $this;
    }

    /**
     * Get type.
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    public function convertToArray(): array
    {
        return get_object_vars($this);
    }

    /**
     * Set description.
     *
     * @param string $description
     *
     * @return Action
     */
    public function setDescription($description)
    {
        $this->isChanged('description', $description);
        $this->description = $description;

        return $this;
    }

    /**
     * Get description.
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set name.
     *
     * @param string $name
     *
     * @return Action
     */
    public function setName($name)
    {
        $this->isChanged('name', $name);
        $this->name = $name;

        return $this;
    }

    /**
     * Get name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }
}
