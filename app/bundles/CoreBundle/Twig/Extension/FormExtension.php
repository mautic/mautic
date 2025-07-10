<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Twig\Extension;

use Mautic\FormBundle\Helper\FormFieldHelper;
use Symfony\Component\Form\FormView;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class FormExtension extends AbstractExtension
{
    public function getFunctions()
    {
        return [
            new TwigFunction('formFieldFormatList', [$this, 'formatList'], ['is_safe' => ['all']]),
            new TwigFunction('formContainsErrors', [$this, 'containsErrors'], ['is_safe' => ['all']]),
        ];
    }

    /**
     * @param array<string> $v
     */
    public function formatList(string $format, array $v): string
    {
        return FormFieldHelper::formatList($format, $v);
    }

    /**
     * Checks to see if the form and its children has an error.
     *
     * @param array<string> $excluding
     */
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
