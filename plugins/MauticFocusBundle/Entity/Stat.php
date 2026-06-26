<?php

namespace MauticPlugin\MauticFocusBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Mautic\LeadBundle\Entity\Lead;

class Stat
{
    // Used for querying stats
    public const TYPE_FORM         = 'submission';

    public const TYPE_CLICK        = 'click';

    public const TYPE_NOTIFICATION = 'view';

    /**
     * @var int
     */
    private $id;

    /**
     * @var Focus
     */
    private $focus;

    /**
     * @var string
     */
    private $type;

    /**
     * @var int|null
     */
    private $typeId;

    /**
     * @var \DateTimeInterface
     */
    private $dateAdded;

    /**
     * @var ?Lead
     */
    private $lead;

    public static function loadMetadata(ORM\ClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable('focus_stats')
            ->setCustomRepositoryClass(StatRepository::class)
            ->addIndex(['type'], 'focus_type')
            ->addIndex(['type', 'type_id'], 'focus_type_id')
            ->addIndex(['date_added'], 'focus_date_added');

        $builder->addId();

        $builder->createManyToOne('focus', 'Focus')
            ->addJoinColumn('focus_id', 'id', false, false, 'CASCADE')
            ->build();

        $builder->addField('type', 'string');

        $builder->addNamedField('typeId', 'integer', 'type_id', true);

        $builder->addNamedField('dateAdded', 'datetime', 'date_added');

        $builder->addLead(true, 'SET NULL');
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return mixed
     */
    public function getFocus()
    {
        return $this->focus;
    }

    /**
     * @param mixed $focus
     */
    public function setFocus($focus): static
    {
        $this->focus = $focus;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param mixed $type
     */
    public function setType($type): static
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getTypeId()
    {
        return $this->typeId;
    }

    /**
     * @param mixed $typeId
     */
    public function setTypeId($typeId): static
    {
        $this->typeId = $typeId;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getDateAdded()
    {
        return $this->dateAdded;
    }

    /**
     * @param mixed $dateAdded
     */
    public function setDateAdded($dateAdded): static
    {
        $this->dateAdded = $dateAdded;

        return $this;
    }

    /**
     * @return ?Lead
     */
    public function getLead()
    {
        return $this->lead;
    }

    public function setLead(Lead $lead): static
    {
        $this->lead = $lead;

        return $this;
    }
}
