<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Twig\Extension;

use Mautic\FormBundle\Helper\FormFieldHelper;
use Symfony\Component\Form\FormRendererInterface;
use Symfony\Component\Form\FormView;
use Twig\Attribute\AsTwigFunction;

final readonly class FormExtension
{
    public function __construct(
        private FormRendererInterface $formRenderer,
    ) {
    }

    /**
     * @param array<string> $v
     */
    #[AsTwigFunction(name: 'formFieldFormatList', isSafe: ['all'])]
    public function formatList(string $format, array $v): string
    {
        return FormFieldHelper::formatList($format, $v);
    }

    /**
     * Checks to see if the form and its children has an error.
     *
     * @param array<string> $excluding
     */
    #[AsTwigFunction(name: 'formContainsErrors', isSafe: ['all'])]
    public function containsErrors(FormView $form, array $excluding = []): bool
    {
        if (count($form->vars['errors']) > 0) {
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

    /**
     * @param array<string, mixed> $variables
     */
    #[AsTwigFunction(name: 'formRowIfExists', isSafe: ['html'])]
    public function rowIfExists(FormView $form, string $fieldName, array $variables = []): string
    {
        if (!isset($form[$fieldName])) {
            return '';
        }

        return $this->formRenderer->searchAndRenderBlock($form[$fieldName], 'row', $variables);
    }
}
