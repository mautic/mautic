<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Templating\Twig\Extension;

use Mautic\CoreBundle\Templating\Helper\FormHelper;
use Mautic\FormBundle\Helper\FormFieldHelper;
use Symfony\Component\Form\FormView;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class FormExtension extends AbstractExtension
{
    private FormHelper $helper;

    public function __construct(FormHelper $helper)
    {
        $this->helper = $helper;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new TwigFunction('formFieldFormatList', [$this, 'formatList'], ['is_safe' => ['all']]),
            new TwigFunction('formContainsErrors', [$this, 'containsErrors']),
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
     * @see FormHelper::containsErrors
     */
    public function containsErrors(FormView $form, array $exluding = []): bool
    {
        return $this->helper->containsErrors($form, $exluding);
    }
}
