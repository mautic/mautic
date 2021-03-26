<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Entity;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;

class CommonEntity
{
    /**
     * @var array
     */
    protected $changes = [];

    /**
     * @var array
     */
    protected $pastChanges = [];

    public static function loadMetadata(ORM\ClassMetadata $metadata)
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setMappedSuperClass();
    }

    /**
     * Wrapper function for isProperty methods.
     *
     * @param string $name
     * @param        $arguments
     *
     * @throws \InvalidArgumentException
     */
    public function __call($name, $arguments)
    {
        if (0 === strpos($name, 'is') && method_exists($this, 'get'.ucfirst($name))) {
            return $this->{'get'.ucfirst($name)}();
        } elseif ('getName' == $name && method_exists($this, 'getTitle')) {
            return $this->getTitle();
        }

        throw new \InvalidArgumentException('Method '.$name.' not exists');
    }

    /**
     * @return string
     */
    public function __toString()
    {
        $string = get_called_class();
        if (method_exists($this, 'getId')) {
            $string .= ' with ID #'.$this->getId();
        }

        return $string;
    }

    /**
     * @param string $prop
     * @param mixed  $val
     */
    protected function isChanged($prop, $val)
    {
        $getter  = (method_exists($this, $prop)) ? $prop : 'get'.ucfirst($prop);
        $current = $this->$getter();
        if ('category' == $prop) {
            $currentId = ($current) ? $current->getId() : '';
            $newId     = ($val) ? $val->getId() : null;
            if ($currentId != $newId) {
                $this->addChange($prop, [$currentId, $newId]);
            }
        } elseif ($current !== $val) {
            if ($current instanceof Collection || $val instanceof Collection) {
                if (!isset($this->changes[$prop])) {
                    $this->changes[$prop] = [
                        'added'   => [],
                        'removed' => [],
                    ];
                }

                if (is_object($val)) {
                    // Entity is getting added to the collection
                    $this->changes['added'][] = method_exists($val, 'getId') ? $val->getId() : (string) $val;
                } else {
                    // Entity is getting removed from the collection
                    $this->changes['removed'][] = $val;
                }
            } else {
                if ($current instanceof \DateTime) {
                    $current = $current->format('c');
                } elseif (is_object($current)) {
                    $current = (method_exists($current, 'getId')) ? $current->getId() : (string) $current;
                } elseif (('' === $current && null === $val) || (null === $current && '' === $val)) {
                    // Ingore empty conversion (but allow 0 to '' or null)
                    return;
                }

                if ($val instanceof \DateTime) {
                    $val = $val->format('c');
                } elseif (is_object($val)) {
                    $val = (method_exists($val, 'getId')) ? $val->getId() : (string) $val;
                }

                $this->addChange($prop, [$current, $val]);
            }
        }
    }

    /**
     * @param $key
     * @param $value
     */
    protected function addChange($key, $value)
    {
        if (isset($this->changes[$key]) && is_array($this->changes[$key]) && [0, 1] !== array_keys($this->changes[$key])) {
            $this->changes[$key] = array_merge($this->changes[$key], $value);
        } else {
            $this->changes[$key] = $value;
        }
    }

    /**
     * @return array
     */
    public function getChanges($includePast = false)
    {
        if ($includePast && empty($this->changes) && !empty($this->pastChanges)) {
            return $this->pastChanges;
        }

        return $this->changes;
    }

    /**
     * Reset changes.
     */
    public function resetChanges()
    {
        $this->pastChanges = $this->changes;
        $this->changes     = [];
    }

    public function setChanges(array $changes)
    {
        $this->changes = $changes;
    }
}
