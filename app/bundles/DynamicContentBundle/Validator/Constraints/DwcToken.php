<?php

declare(strict_types=1);

namespace Mautic\DynamicContentBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

#[\Attribute]
class DwcToken extends Constraint
{
    public string $message = 'mautic.dynamicContent.should_not_have_empty_content';
}
