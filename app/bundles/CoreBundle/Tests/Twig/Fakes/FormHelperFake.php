<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Twig\Fakes;

use Mautic\CoreBundle\Templating\Helper\FormHelper;
use Symfony\Component\Form\FormView;

class FormHelperFake extends FormHelper
{
    public function __construct()
    {
    }

    /**
     * @param FormView|array<string> $form
     * @param mixed[]                $variables
     */
    public function rowIfExists($form, string $key, string $template = null, $variables = [])
    {
        return "<input name=\"{$key}\" />";
    }
}
