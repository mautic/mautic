<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Validator\Constraints;

use Mautic\LeadBundle\Validator\Constraints\Length;
use Mautic\LeadBundle\Validator\Constraints\LengthValidator;
use PHPUnit\Framework\Attributes\DoesNotPerformAssertions;
use PHPUnit\Framework\TestCase;

final class LengthValidatorTest extends TestCase
{
    #[DoesNotPerformAssertions]
    public function testValidate(): void
    {
        $constraint = new Length(min: 3);
        $validator  = new LengthValidator();

        $validator->validate('valid', $constraint);
        // Not thrownig Symfony\Component\Validator\Exception\UnexpectedTypeException
        $validator->validate(['0', '1'], $constraint);
    }
}
