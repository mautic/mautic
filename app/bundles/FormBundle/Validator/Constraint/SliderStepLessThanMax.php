<?php

declare(strict_types=1);

namespace Mautic\FormBundle\Validator\Constraint;

use Symfony\Component\Validator\Constraint;

final class SliderStepLessThanMax extends Constraint
{
    public string $message = 'mautic.form.field.form.slider_step_lt_max_error';
}
