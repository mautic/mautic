<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Twig\Extension;

use Mautic\FormBundle\Helper\FormFieldHelper;
use Symfony\Component\Form\FormView;

class FormExtension
{
    /**
     * @param array<string> $v
     */
    #[\Twig\Attribute\AsTwigFunction('formFieldFormatList', isSafe: ['all'])]
    public function formatList(string $format, array $v): string
    {
        return FormFieldHelper::formatList($format, $v);
    }

    /**
     * Checks to see if the form and its children has an error.
     *
     * @param array<string> $excluding
     */
    #[\Twig\Attribute\AsTwigFunction('formContainsErrors', isSafe: ['all'])]
    public function containsErrors(FormView $form, array $excluding = []): bool
    {
        if (count($form->vars['errors'])) {
            return true;
        }
        foreach ($form->children as $key => $child) {
            if (in_array($key, $excluding)) {
                continue;
            }

            if (isset($child->vars['errors']) && count($child->vars['errors'])) {
                return true;
            }

            if (count($child->children)) {
                $hasErrors = $this->containsErrors($child);
                if ($hasErrors) {
                    return true;
                }
            }
        }

        return false;
    }
}
