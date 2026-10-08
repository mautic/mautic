<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Validator\Constraints;

use Mautic\LeadBundle\Validator\Constraints\Length;
use Mautic\LeadBundle\Validator\Constraints\LengthValidator;
use PHPUnit\Framework\TestCase;

final class LengthTest extends TestCase
{
    public function testValidateBy(): void
    {
        $constraint = new Length(min: 3);
        $this->assertSame(LengthValidator::class, $constraint->validatedBy());
    }
}
