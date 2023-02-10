<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Twig\Fakes;

use Mautic\CoreBundle\Templating\Helper\FormHelper;

class FormHelperFake extends FormHelper
{
    public function __construct()
    {
    }

    public function rowIfExists($form, $key, $template = null, $variables = [])
    {
        return "<input name=\"{$key}\" />";
    }
}
