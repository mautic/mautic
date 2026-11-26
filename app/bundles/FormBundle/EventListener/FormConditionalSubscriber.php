<?php

declare(strict_types=1);

namespace Mautic\FormBundle\EventListener;

use Mautic\CoreBundle\Event\EntityPostSave;
use Mautic\FormBundle\Entity\Form;
use Mautic\FormBundle\Model\FieldModel;
use Mautic\FormBundle\Model\FormModel;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

final readonly class FormConditionalSubscriber
{
    public function __construct(
        private FormModel $formModel,
        private FieldModel $fieldModel,
    ) {
    }

    /**
     * Replace session field Id with field Id after save entity.
     */
    #[AsEventListener(
        event: EntityPostSave::class,
    )]
    public function onFormPostSave(EntityPostSave $event): void
    {
        $form = $event->getEntity();
        if (!$form instanceof Form) {
            return;
        }

        // Process temporary field ID to real field ID
        $actualFieldIds = [];
        foreach ($form->getFields() as $field) {
            $actualFieldIds[] = $field->getId();
            if (str_contains((string) $field->getParent(), 'new')) {
                foreach ($form->getFields() as $parentField) {
                    if ($field->getParent() === $parentField->getSessionId()) {
                        $field->setParent((string) $parentField->getId());
                        $this->fieldModel->saveEntity($field);
                    }
                }
            }
        }

        // Delete child fields
        $deleteIds = [];
        foreach ($form->getFields() as $field) {
            if ($field->getParent() && !in_array($field->getParent(), $actualFieldIds)) {
                $deleteIds[] = $field->getId();
            }
        }

        if ([] !== $deleteIds) {
            $this->formModel->deleteFields($form, $deleteIds);
        }
    }
}
