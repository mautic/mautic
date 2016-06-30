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
use Symfony\Component\Validator\Constraints\Date;

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
    private $lastShoot;

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
     *
     * @var \DateTime
     */
    private $triggerDate;

    /**
     *
     * @var string
     */
    private $triggerMode;

    /**
     *
     * @var int
     */
    private $triggerInterval = null;

    /**
     *
     * @var string
     */
    private $triggerIntervalUnit = null;

    /**
     *
     * @var array
     */
    private $weekDays = null;

    public static function loadMetadata(ORM\ClassMetadata $metadata)
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable('periodicity')->setCustomRepositoryClass('Mautic\CoreBundle\Entity\PeriodicityRepository');

        $builder->addId();

        $builder->createField('lastShoot', 'datetime')
            ->columnName('last_shoot')
            ->nullable()
            ->build();

        $builder->createField('triggerDate', 'datetime')
            ->columnName('trigger_date')
            ->build();

        $builder->createField('triggerMode', 'string')
            ->columnName('trigger_mode')
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

        $builder->createField('weekDays', 'array')
            ->columnName('days_of_week')
            ->length(1)
            ->nullable()
            ->build();
    }

    // Statics functions for the possibles types
    public static function getTypeEmail()
    {
        return "email:generate";
    }

    public static function getTypeSms()
    {
        return "sms:generate";
    }

    /**
     *
     * @var array
     */
    protected $changes;

    /**
     * @param $prop
     * @param $val
     *
     * @return void
     */
    protected function isChanged($prop, $val)
    {
        $getter = "get" . ucfirst($prop);
        $current = $this->$getter();
        if ($current != $val) {
            $this->changes[$prop] = array(
                $current,
                $val
            );
        }
    }

    /**
     *
     * @return array
     */
    public function getChanges()
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
     * Get nextShoot
     *
     * @return date
     */
    public function getNextShoot()
    {
        return $this->nextShoot;
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
     * @return string
     */
    public function getTriggerMode()
    {
        return $this->triggerMode;
    }

    /**
     *
     * @param string $triggerMode
     * @return Periodicity
     */
    public function setTriggerMode($triggerMode)
    {
        $this->triggerMode = $triggerMode;
        return $this;
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

    public function getLastShoot()
    {
        return $this->lastShoot;
    }

    public function setLastShoot($lastShoot)
    {
        $this->lastShoot = $lastShoot;
        return $this;
    }

    /**
     * @return null|array
     */
    public function getWeekDays() {
        return $this->weekDays;
    }

    /**
     * @param array $weekDays
     *
     * @return Periodicity
     */
    public function setWeekDays($weekDays) {
        $this->weekDays = $weekDays;
        return $this;
    }

    /**
     * nextShoot
     *
     * @return date
     */
    public function nextShoot()
    {
        // define date of next execution
        if (is_null($this->getLastShoot())) {
            // last shoot was never set... mean it's the first execution of this periodicity
            return $this->getTriggerDate();
        }
        switch ($this->getTriggerMode()) {

            case 'weekDays':
                $allowedDates = array();
                foreach ($this->getWeekDays() as $allowedDay) {
                    $allowedDates[] = clone $this->getLastShoot()
                        ->setTime(0, 0)
                        ->modify('next ' . $allowedDay);
                }

                $nextShoot = min($allowedDates);
                break;

            case 'timeInterval':

                switch ($this->getTriggerIntervalUnit()) {
                    case 'd':
                        /** @var \DateTime $nextShoot */
                        $nextShoot = clone $this->getLastShoot()
                            ->setTime(0, 0)
                            ->modify('+' . $this->getTriggerInterval() . 'day');
                        break;
                    case 'w':
                        /** @var \DateTime $nextShoot */
                        $nextShoot = clone $this->getLastShoot()
                            ->setTime(0, 0)
                            ->modify('+' . $this->getTriggerInterval() . 'week');

                        break;
                    case 'm':
                        /** @var \DateTime $nextShoot */
                        $nextShoot = clone $this->getLastShoot()
                            ->setTime(0, 0)
                            ->modify('+' . $this->getTriggerInterval() . 'month');
                        break;
                }
                break;
        }

        // Modifiy execution time according to triggerdate time
        $nextShoot->setTime($this->getTriggerDate()
            ->format('H'), $this->getTriggerDate()
            ->format('i'));
//         echo (clone $nextShoot->format('d/m/Y'));
        return $nextShoot;
    }
}
