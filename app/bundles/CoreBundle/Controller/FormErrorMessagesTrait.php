<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Controller;

use Symfony\Component\Form\Form;

trait FormErrorMessagesTrait
{
    /**
     * @param array $formErrors
     *
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
     * @param Form $form
     *
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

    public function getFormErrorForBuilder(Form $form)
    {
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
