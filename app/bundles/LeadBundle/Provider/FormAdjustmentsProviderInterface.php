<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Provider;

use Symfony\Component\Form\FormInterface;

interface FormAdjustmentsProviderInterface
{
    /**
     * Allows subscribers to adjust a form so new fields can be added, deleted or modified.
     *
     * @param FormInterface<FormInterface> $form
     * @param mixed[]                      $fieldDetails
     *
     * @return FormInterface<FormInterface>
     */
    public function adjustForm(FormInterface $form, string $fieldAlias, string $fieldObject, string $operator, array $fieldDetails): FormInterface;
}
