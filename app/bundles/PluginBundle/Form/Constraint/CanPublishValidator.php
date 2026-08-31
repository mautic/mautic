<?php

declare(strict_types=1);

namespace Mautic\PluginBundle\Form\Constraint;

use Mautic\PluginBundle\Event\PluginIsPublishedEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

final class CanPublishValidator extends ConstraintValidator
{
    public function __construct(
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (1 !== $value) {
            return;
        }
        if (!$constraint instanceof CanPublish) {
            throw new UnexpectedTypeException($constraint, CanPublish::class);
        }
        $event = new PluginIsPublishedEvent($value, $constraint->integrationName);
        $event = $this->eventDispatcher->dispatch($event);

        if (!$event->isCanPublish()) {
            $this->context->buildViolation($event->getMessage())
                ->addViolation();
        }
    }
}
