<?php

declare(strict_types=1);

namespace Mautic\FormBundle\EventListener;

use Mautic\FormBundle\Event\FormFieldEvent;
use Mautic\FormBundle\FormEvents;
use Mautic\FormBundle\Model\FieldModel;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final readonly class FormFieldSubscriber
{
    public function __construct(
        private FieldModel $fieldModel,
    ) {
    }

    #[\Symfony\Component\EventDispatcher\Attribute\AsEventListener(event: FormEvents::FIELD_POST_DELETE)]
    public function onFieldPostDelete(FormFieldEvent $event): void
    {
        $field = $event->getField();

        if (null !== $field->deletedId) {
            $this->fieldModel->removeFieldColumn($field);
        }
    }
}
