<?php

namespace Mautic\CoreBundle\Event;

use Doctrine\ORM\EntityManagerInterface;
use Mautic\LeadBundle\Entity\Lead;
use Symfony\Contracts\EventDispatcher\Event;

class CommonEvent extends Event
{
    /**
     * @var EntityManagerInterface
     */
    protected $em;

    /**
     * @var object
     */
    protected $entity;

    /**
     * @var bool
     */
    protected $isNew = true;

    /**
     * @var bool|array
     */
    protected $changes;

    /**
     * @var string
     */
    protected $failed;

    /**
     * Sets the entity manager for the event to use.
     *
     * @param EntityManagerInterface $em
     */
    public function setEntityManager($em): void
    {
        $this->em = $em;
    }

    /**
     * Returns if a saved lead is new or not.
     *
     * @return bool
     */
    public function isNew()
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
