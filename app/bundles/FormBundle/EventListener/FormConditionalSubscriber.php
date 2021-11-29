<?php

declare(strict_types=1);

/*
 * @copyright   2020 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\FormBundle\EventListener;

use Mautic\FormBundle\Event\FormEvent;
use Mautic\FormBundle\FormEvents;
use Mautic\FormBundle\Model\FieldModel;
use Mautic\FormBundle\Model\FormModel;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class FormConditionalSubscriber implements EventSubscriberInterface
{
    /**
     * @var FormModel
     */
    private $formModel;

    /**
     * @var FieldModel
     */
    private $fieldModel;

    public function __construct(FormModel $formModel, FieldModel $fieldModel)
    {
        $this->formModel  = $formModel;
        $this->fieldModel = $fieldModel;
    }

    /**
     * @return array<string,mixed[]>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            FormEvents::FORM_POST_SAVE => ['onFormPostSave', 0],
        ];
    }

    /**
     * Replace session field Id with field Id after save entity.
     */
    public function onFormPostSave(FormEvent $event): void
    {
        $form = $event->getForm();

        // Process temporary field ID to real field ID
        $actualFieldIds = [];
        foreach ($form->getFields() as $field) {
            $actualFieldIds[] = $field->getId();
            if (false !== strpos((string) $field->getParent(), 'new')) {
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

        if (!empty($deleteIds)) {
            $this->formModel->deleteFields($form, $deleteIds);
        }
    }
}
