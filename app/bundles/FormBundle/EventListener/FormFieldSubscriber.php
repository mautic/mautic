<?php

declare(strict_types=1);

namespace Mautic\FormBundle\EventListener;

use Mautic\FormBundle\Event\FormFieldEvent;
use Mautic\FormBundle\FormEvents;
use Mautic\FormBundle\Model\FieldModel;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class FormFieldSubscriber implements EventSubscriberInterface
{
    public function __construct(private FieldModel $fieldModel)
    {
    }

    /**
     * @return mixed[]
     */
    public static function getSubscribedEvents(): array
    {
        return [
            FormEvents::FIELD_POST_DELETE => ['onFieldPostDelete', 0],
        ];
    }

    public function onFieldPostDelete(FormFieldEvent $event): void
    {
        $field = $event->getField();

        if (isset($field->deletedId)) {
            $this->fieldModel->removeFieldColumn($field);
        }
    }
}
