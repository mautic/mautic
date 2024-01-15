<?php

namespace Mautic\PluginBundle\Form\Constraint;

use Mautic\PluginBundle\Event\PluginIsPublishedEvent;
use Mautic\PluginBundle\PluginEvents;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class CanPublishValidator extends ConstraintValidator
{
    private EventDispatcherInterface $eventDispatcher;

    public function __construct(EventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    public function validate($value, Constraint $constraint): void
    {
        if (1 !== $value) {
            return;
        }
        if (!$constraint instanceof CanPublish) {
            throw new \Symfony\Component\Validator\Exception\UnexpectedTypeException($constraint, CanPublish::class);
        }
        $event = new PluginIsPublishedEvent($value, $constraint->integrationName);
        $event = $this->eventDispatcher->dispatch(PluginEvents::PLUGIN_IS_PUBLISHED_STATE_CHANGING, $event);

        if (!$event->isCanPublish()) {
            $this->context->buildViolation($event->getMessage())
                ->addViolation();
        }
    }
}
