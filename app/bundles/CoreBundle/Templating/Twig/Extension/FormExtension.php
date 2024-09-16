<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Templating\Twig\Extension;

use Mautic\FormBundle\Helper\FormFieldHelper;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class FormExtension extends AbstractExtension
{
    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new TwigFunction('formFieldFormatList', [$this, 'formatList'], ['is_safe' => ['all']]),
        ];
    }

    /**
     * @param array<string> $v
     */
    public function formatList(string $format, array $v): string
    {
        return FormFieldHelper::formatList($format, $v);
    }
}
