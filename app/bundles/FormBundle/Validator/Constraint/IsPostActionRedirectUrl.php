<?php

declare(strict_types=1);

namespace Mautic\FormBundle\Validator\Constraint;

use Symfony\Component\Validator\Constraint;

final class IsPostActionRedirectUrl extends Constraint
{
    public string $message = 'mautic.form.form.postactionproperty_redirect.url';
}
