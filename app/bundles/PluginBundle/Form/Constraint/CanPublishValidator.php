<?php

declare(strict_types=1);

namespace Mautic\PluginBundle\Form\Constraint;

use Mautic\PluginBundle\Event\PluginIsPublishedEvent;
use Mautic\PluginBundle\PluginEvents;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class CanPublishValidator extends ConstraintValidator
{
    public function __construct(private EventDispatcherInterface $eventDispatcher)
    {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (1 !== $value) {
            return;
        }
        if (!$constraint instanceof CanPublish) {
            throw new \Symfony\Component\Validator\Exception\UnexpectedTypeException($constraint, CanPublish::class);
        }
        $event = new PluginIsPublishedEvent($value, $constraint->integrationName);
        $event = $this->eventDispatcher->dispatch($event, PluginEvents::PLUGIN_IS_PUBLISHED_STATE_CHANGING);

        if (!$event->isCanPublish()) {
            $this->context->buildViolation($event->getMessage())
                ->addViolation();
        }
    }
}
