<?php

namespace Mautic\CoreBundle\Controller;

use Symfony\Component\Form\Form;

trait FormErrorMessagesTrait
{
    /**
     * @return string
     */
    public function getFormErrorMessage(array $formErrors)
    {
        $msg = '';

        if ($formErrors) {
            foreach ($formErrors as $key => $error) {
                if (!$error) {
                    continue;
                }

                if ($msg) {
                    $msg .= ', ';
                }

                if (is_string($key)) {
                    $msg .= $key.': ';
                }

                if (is_array($error)) {
                    $msg .= $this->getFormErrorMessage($error);
                } else {
                    $msg .= $error;
                }
            }
        }

        return $msg;
    }

    /**
     * @return array
     */
    public function getFormErrorMessages(Form $form)
    {
        $errors = [];

        foreach ($form->getErrors(true) as $error) {
            if (isset($errors[$error->getOrigin()->getName()])) {
                $errors[$error->getOrigin()->getName()] = [$error->getMessage()];
            } else {
                $errors[$error->getOrigin()->getName()][] = $error->getMessage();
            }
        }

        return $errors;
    }

    public function getFormErrorCodes(Form $form): array
    {
        $codes = [];

        foreach ($form->getErrors(true) as $error) {
            $code         = $error->getCause()->getCode();
            $codes[$code] = $code;
        }

        return $codes;
    }

    public function getFormErrorForBuilder(Form $form)
    {
        if (!$form->isSubmitted()) {
            return null;
        }

        if ($form->isValid()) {
            return null;
        }

        $validationErrors = $this->getFormErrorMessages($form);

        if (!$validationErrors) {
            return null;
        }

        $validationError = $this->getFormErrorMessage($validationErrors);

        return $this->get('translator')->trans('mautic.core.form.builder.error', ['%error%' => $validationError]);
    }
}
