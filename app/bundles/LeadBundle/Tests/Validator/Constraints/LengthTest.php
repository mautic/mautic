<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Validator\Constraints;

use PHPUnit\Framework\TestCase;
use Mautic\LeadBundle\Validator\Constraints\Length;
use Mautic\LeadBundle\Validator\Constraints\LengthValidator;

final class LengthTest extends TestCase
{
    public function testValidateBy(): void
    {
        $constraint = new Length(min: 3);
        $this->assertSame(LengthValidator::class, $constraint->validatedBy());
    }
}
