<?php

namespace Mautic\CoreBundle\Event;

use Mautic\LeadBundle\Entity\Lead;
use Symfony\Contracts\EventDispatcher\Event;

abstract class CommonEvent extends Event
{
    /**
     * @var object
     */
    protected $entity;

    protected bool $isNew = true;

    /**
     * @var bool|array
     */
    protected $changes;

    /**
     * @var string
     */
    protected $failed;

    /**
     * Returns if a saved lead is new or not.
     */
    public function isNew(): bool
    {
        return $this->isNew;
    }

    public function setFailed(string $reason): void
    {
        $this->failed = $reason;
    }

    /**
     * Gets changes to original entity.
     *
     * @return mixed
     */
    public function getChanges()
    {
        if (null === $this->changes) {
            $this->changes = false;
            if ($this->entity && method_exists($this->entity, 'getChanges')) {
                $this->changes = $this->entity->getChanges();
                // Reset changes
                if (method_exists($this->entity, 'resetChanges')) {
                    $this->entity->resetChanges();
                }
            }
        }

        return $this->changes;
    }

    /**
     * @return Lead|null
     */
    public function getLead()
    {
        if (method_exists($this->entity, 'getLead')) {
            return $this->entity->getLead();
        }

        return null;
    }
}
