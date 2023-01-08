<?php

namespace Mautic\ApiBundle\Form\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class OAuthCallback extends Constraint
{
    public $message = 'The callback URL is invalid.';

    public function validatedBy()
    {
        return OAuthCallbackValidator::class;
    }
}
