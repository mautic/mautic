<?php

namespace Mautic\FormBundle\Form\Type;

use Mautic\FormBundle\Model\FieldModel;
use Mautic\FormBundle\Model\FormModel;

trait FormFieldTrait
{
    /**
     * @var FieldModel
     */
    protected $fieldModel;

    /**
     * @var FormModel
     */
    protected $formModel;

    public function setFieldModel(FieldModel $fieldModel): void
    {
        $this->fieldModel = $fieldModel;
    }

    public function setFormModel(FormModel $formModel): void
    {
        $this->formModel = $formModel;
    }

    /**
     * @param bool $asTokens
     */
    protected function getFormFields($formId, $asTokens = true): array
    {
        $fields   = $this->fieldModel->getSessionFields($formId);
        $viewOnly = $this->formModel->getCustomComponents()['viewOnlyFields'];

        $choices = [];

        foreach ($fields as $f) {
            if (in_array($f['type'], $viewOnly)) {
                continue;
            }

            $choices[($asTokens) ? '{formfield='.$f['alias'].'}' : $f['alias']] = $f['label'];
        }

        return $choices;
    }
}
