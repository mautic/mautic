<?php

/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
namespace Mautic\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;

class Periodicity
{
    /**
     *
     * @var integer
     */
    private $id;

    /**
     *
     * @var date
     */
    private $nextShoot;

    /**
     *
     * @var string
     */
    private $name;

    /**
     *
     * @var string
     */
    private $type;
    /**
     *
     * @var integer
     */
    private $targetId;

    /**
     * @var null|\DateTime
     */
    private $triggerDate;

    /**
     * @var int
     */
    private $triggerInterval = 0;

    /**
     * @var string
     */
    private $triggerIntervalUnit;

    public static function loadMetadata(ORM\ClassMetadata $metadata)
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable('periodicity')->setCustomRepositoryClass('Mautic\CoreBundle\Entity\PeriodicityRepository');
        // ->addIndex(array('object', 'object_id'), 'object_search')
        // ->addIndex(array('bundle', 'object', 'action', 'object_id'), 'timeline_search')

        $builder->addId();

        $builder->createField('nextShoot', 'date')
            ->columnName('next_shoot')
            ->build();

        $builder->createField('triggerDate', 'datetime')
            ->columnName('trigger_date')
            ->nullable()
            ->build();

        $builder->createField('triggerInterval', 'integer')
            ->columnName('trigger_interval')
            ->nullable()
            ->build();

        $builder->createField('triggerIntervalUnit', 'string')
            ->columnName('trigger_interval_unit')
            ->length(1)
            ->nullable()
            ->build();

        $builder->createField('name', 'string')
            ->columnName('name')
            ->length(50)
            ->build();

        $builder->createField('type', 'string')
            ->columnName('type')
            ->length(50)
            ->build();

        $builder->createField('targetId', 'integer')
            ->columnName('target_id')
            ->build();
    }

    /**
     * Set userId
     *
     * @param date $nextShoot
     *
     * @return date
     */
    public function setNextShoot($nextShoot)
    {
        $this->nextShoot = $nextShoot;

        return $this;
    }

    /**
     * Get nextShoot
     *
     * @return date
     */
    public function getNextShoot()
    {
        return $this->nextShoot;
    }

    /**
     * Set name
     *
     * @param string $name
     *
     * @return Event
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set type
     *
     * @param string $type
     *
     * @return Event
     */
    public function setType($type)
    {
        $this->isChanged('type', $type);
        $this->type = $type;

        return $this;
    }

    /**
     * Get type
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set order
     *
     * @param integer $order
     *
     * @return integer
     */
    public function setTargetId($targetId)
    {
        $this->targetId = $targetId;

        return $this;
    }

    /**
     * Get order
     *
     * @return integer
     */
    public function getTargetId()
    {
        return $this->targetId;
    }

    /**
     *
     * @return mixed
     */
    public function getTriggerDate()
    {
        return $this->triggerDate;
    }

    /**
     *
     * @param mixed $triggerDate
     */
    public function setTriggerDate($triggerDate)
    {
        $this->isChanged('triggerDate', $triggerDate);
        $this->triggerDate = $triggerDate;
    }

    /**
     *
     * @return integer
     */
    public function getTriggerInterval()
    {
        return $this->triggerInterval;
    }

    /**
     *
     * @param integer $triggerInterval
     */
    public function setTriggerInterval($triggerInterval)
    {
        $this->isChanged('triggerInterval', $triggerInterval);
        $this->triggerInterval = $triggerInterval;
    }

    /**
     *
     * @return mixed
     */
    public function getTriggerIntervalUnit()
    {
        return $this->triggerIntervalUnit;
    }

    /**
     *
     * @param mixed $triggerIntervalUnit
     */
    public function setTriggerIntervalUnit($triggerIntervalUnit)
    {
        $this->isChanged('triggerIntervalUnit', $triggerIntervalUnit);
        $this->triggerIntervalUnit = $triggerIntervalUnit;
    }
}
