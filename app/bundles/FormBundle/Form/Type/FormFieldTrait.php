<?php

namespace Mautic\FormBundle\Form\Type;

use Mautic\FormBundle\Model\FieldModel;
use Mautic\FormBundle\Model\FormModel;
use Symfony\Contracts\Service\Attribute\Required;

trait FormFieldTrait
{
    protected FieldModel $fieldModel;

    protected FormModel $formModel;

    #[Required]
    public function autowireFormFieldTrait(
        FieldModel $fieldModel,
        FormModel $formModel,
    ): void {
        $this->fieldModel = $fieldModel;
        $this->formModel = $formModel;
    }

    protected function getFormFields($formId, bool $asTokens = true): array
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
