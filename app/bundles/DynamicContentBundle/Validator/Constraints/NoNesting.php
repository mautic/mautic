<?php

declare(strict_types=1);

namespace Mautic\DynamicContentBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * This constraint checks if the content does not include another DWC token.
 */
class NoNesting extends Constraint
{
    public string $message = 'mautic.dynamicContent.no_nesting';
}
