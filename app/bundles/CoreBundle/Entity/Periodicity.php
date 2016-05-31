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

    /**
     * @var integer
     */
    private $daysOfWeekMask;

    public static function loadMetadata(ORM\ClassMetadata $metadata)
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable('periodicity')->setCustomRepositoryClass('Mautic\CoreBundle\Entity\PeriodicityRepository');
        // ->addIndex(array('object', 'object_id'), 'object_search')
        // ->addIndex(array('bundle', 'object', 'action', 'object_id'), 'timeline_search')

        $builder->addId();

        $builder->createField('nextShoot', 'datetime')
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

        $builder->createField('type', 'string')
            ->columnName('type')
            ->length(50)
            ->build();

        $builder->createField('targetId', 'integer')
            ->columnName('target_id')
            ->build();

        $builder->createField('daysOfWeekMask', 'integer')
            ->columnName('days_of_week_mask')
            ->length(1)
            ->nullable()
            ->build();
    }

    //Statics functions for the possibles types
    public static function getTypeEmail(){  return "email:genarate";}
    public static function getTypeSms(){  	return "sms:genarate";}

    /**
     * @var array
     */
    protected $changes;

    /**
     * @param $prop
     * @param $val
     *
     * @return void
     */
    protected function isChanged ($prop, $val)
    {
        $getter  = "get" . ucfirst($prop);
        $current = $this->$getter();
        if ($current != $val) {
            $this->changes[$prop] = array($current, $val);
        }
    }

    /**
     * @return array
     */
    public function getChanges ()
    {
        return $this->changes;
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getid()
    {
        return $this->id;
    }

    /**
     * Set nextShoot
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

    public static function getDaysOfWeek(){
        return ["Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday", "Sunday"];
    }

    /**
     *
     * @return array
     */
    public function getDaysOfWeekMask()
    {
        $tmp = $this->daysOfWeekMask;
        $return = array();
        for($i = 6; $i >= 0; $i--){
            if($tmp >= 2**$i){
                $tmp = $tmp - 2**$i;
                $return[$this->getDaysOfWeek()[6-$i]] = true;
            }
            else{
                $return[$this->getDaysOfWeek()[6-$i]] = false;
            }
        }
        return $return;
    }

    /**
     *
     * @param array $daysOfWeekMask
     */
    public function setDaysOfWeekMask($daysOfWeekMask)
    {
        $tmp = 0;
        for($i = 6; $i >= 0; $i--){
            if($daysOfWeekMask[$this->getDaysOfWeek()[6-$i]] == true){
                $tmp += 2**$i;
            }
        }
        $this->daysOfWeekMask = $tmp;
        return $this;
    }

}
