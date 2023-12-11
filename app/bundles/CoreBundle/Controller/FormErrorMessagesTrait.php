<?php

namespace Mautic\CoreBundle\Controller;

use Symfony\Component\Form\FormInterface;

trait FormErrorMessagesTrait
{
    /**
     * @param array<mixed> $formErrors
     */
    public function getFormErrorMessage(array $formErrors): string
    {
        $msg = '';

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

        return $msg;
    }

    /**
     * @param FormInterface<object> $form
     *
     * @return array<array<string|null>>
     */
    public function getFormErrorMessages(FormInterface $form): array
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

    /**
     * @return array<string|null>
     */
    public function getFormErrorCodes(FormInterface $form): array
    {
        $codes = [];

        foreach ($form->getErrors(true) as $error) {
            $code         = $error->getCause()->getCode();
            $codes[$code] = $code;
        }

        return $codes;
    }

    /**
     * @param FormInterface<object> $form
     */
    public function getFormErrorForBuilder(FormInterface $form): ?string
    {
        if (!$form->isSubmitted() || $form->isValid()) {
            return null;
        }

        $validationErrors = $this->getFormErrorMessages($form);

        if (!$validationErrors) {
            return null;
        }

        $validationError = $this->getFormErrorMessage($validationErrors);

        return $this->translator->trans('mautic.core.form.builder.error', ['%error%' => $validationError]);
    }
}
