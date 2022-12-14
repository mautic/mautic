<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Templating\Twig\Extension;

use Mautic\CoreBundle\Templating\Helper\FormHelper;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class FormCustomExtension extends AbstractExtension
{
    protected FormHelper $formHelper;

    public function __construct(FormHelper $formHelper)
    {
        $this->formHelper = $formHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new TwigFunction('rowIfExists', [$this, 'rowIfExists'], ['is_safe' => ['all']]),
        ];
    }

    /**
     * Render row if it exists.
     *
     * @param       $form
     * @param       $key
     * @param null  $template
     * @param array $variables
     *
     * @return mixed|string
     */
    public function rowIfExists($form, $key, $template = null, array $variables = []): string
    {
        return $this->formHelper->rowIfExists($form, $key, $template, $variables);
    }
}
