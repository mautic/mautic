<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Event;

use Mautic\CoreBundle\Validator\EntityEvent;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Contracts\EventDispatcher\Event;

class EntityValidateEvent extends Event
{
    public function __construct(private object $entity, private EntityEvent $constraint, private ExecutionContextInterface $context)
    {
    }

    public function getEntity(): object
    {
        return $this->entity;
    }

    public function getConstraint(): EntityEvent
    {
        return $this->constraint;
    }

    public function getContext(): ExecutionContextInterface
    {
        return $this->context;
    }
}
