<?php

declare(strict_types=1);

namespace Mautic\MessengerBundle\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
final class Dsn extends Constraint
{
}
