<?php

declare(strict_types=1);

namespace Mautic\FormBundle\EventListener;

use Mautic\CoreBundle\Event\EntityPostDelete;
use Mautic\FormBundle\Entity\Field;
use Mautic\FormBundle\Model\FieldModel;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

final readonly class FormFieldSubscriber
{
    public function __construct(
        private FieldModel $fieldModel,
    ) {
    }

    #[AsEventListener(
        event: EntityPostDelete::class,
    )]
    public function onFieldPostDelete(EntityPostDelete $event): void
    {
        $field = $event->getEntity();
        if (!$field instanceof Field) {
            return;
        }

        if (null !== $field->deletedId) {
            $this->fieldModel->removeFieldColumn($field);
        }
    }
}
