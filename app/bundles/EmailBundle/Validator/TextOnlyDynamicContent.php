<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Validator;

use Symfony\Component\Validator\Constraint;

final class TextOnlyDynamicContent extends Constraint
{
    public string $message = 'mautic.email.subject.dynamic_content.text_only';
}
