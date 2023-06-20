<?php

namespace Mautic\CoreBundle\Entity;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\ClassMetadata;
use Mautic\CategoryBundle\Entity\Category;
use Mautic\ChannelBundle\Entity\Channel;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Mautic\LeadBundle\Entity\FrequencyRule;
use Mautic\LeadBundle\Entity\Tag;
use Mautic\StageBundle\Entity\Stage;
use Mautic\UserBundle\Entity\Role;
use Mautic\UserBundle\Entity\User;
use Mautic\WebhookBundle\Entity\Event;

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

    public static function loadMetadata(ClassMetadata $metadata)
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setMappedSuperClass();
    }

    /**
     * Wrapper function for isProperty methods.
     *
     * @param string $name
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
     * @param string                                                                                                                                                                                                                     $prop
     * @param array<int|string, array|int|string>|\DateTime|\DateTimeInterface|bool|int|string|self|FormEntity|IpAddress|TranslationEntityInterface|VariantEntityInterface|Category|Channel|FrequencyRule|Tag|Stage|User|Event|Role|null $val
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
