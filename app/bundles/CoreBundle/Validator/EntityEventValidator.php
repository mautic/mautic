<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Validator;

use Mautic\CoreBundle\Event\EntityValidateEvent;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class EntityEventValidator extends ConstraintValidator
{
    public function __construct(private EventDispatcherInterface $dispatcher)
    {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!is_object($value)) {
            throw new UnexpectedTypeException($value, 'object');
        }

        if (!$constraint instanceof EntityEvent) {
            throw new UnexpectedTypeException($constraint, EntityEvent::class);
        }

        $this->dispatcher->dispatch(new EntityValidateEvent($value, $constraint, $this->context));
    }
}
