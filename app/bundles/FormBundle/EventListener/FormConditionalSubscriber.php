<?php

/*
 * @copyright   2020 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\FormBundle\EventListener;

use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\FormBundle\Event as Events;
use Mautic\FormBundle\FormEvents;
use Mautic\FormBundle\Model\FieldModel;
use Mautic\FormBundle\Model\FormModel;

/**
 * Class FormSubscriber.
 */
class FormConditionalSubscriber extends CommonSubscriber
{
    /**
     * @var FormModel
     */
    private $formModel;

    /**
     * @var FieldModel
     */
    private $fieldModel;

    /**
     * FormConditionalSubscriber constructor.
     *
     * @param FormModel  $formModel
     * @param FieldModel $fieldModel
     */
    public function __construct(FormModel $formModel, FieldModel $fieldModel)
    {
        $this->formModel  = $formModel;
        $this->fieldModel = $fieldModel;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            FormEvents::FORM_POST_SAVE           => ['onFormPostSave', 0],
        ];
    }

    /**
     * Replace session field Id with field Id after save entity.
     *
     * @param Events\FormEvent $event
     */
    public function onFormPostSave(Events\FormEvent $event)
    {
        $form = $event->getForm();

        // Process temporary fiedl ID to real field ID
        $actualFieldIds = [];
        foreach ($form->getFields() as $field) {
            $actualFieldIds[] = $field->getId();
            if (strpos($field->getParent(), 'new') !== false) {
                foreach ($form->getFields() as $parentField) {
                    if ($field->getParent() == $parentField->getSessionId()) {
                        $field->setParent($parentField->getId());
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
