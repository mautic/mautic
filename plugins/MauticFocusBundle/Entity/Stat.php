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
            ->setCustomRepositoryClass(\MauticPlugin\MauticFocusBundle\Entity\StatRepository::class)
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
     *
     * @return Stat
     */
    public function setFocus($focus)
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
     *
     * @return Stat
     */
    public function setType($type)
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
     *
     * @return Stat
     */
    public function setTypeId($typeId)
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
     *
     * @return Stat
     */
    public function setDateAdded($dateAdded)
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

    /**
     * @return Stat
     */
    public function setLead(Lead $lead)
    {
        $this->lead = $lead;

        return $this;
    }
}
