<?php

namespace Mautic\LeadBundle\Tests\Validator\Constraints;

use Mautic\LeadBundle\Validator\Constraints\Length;
use Mautic\LeadBundle\Validator\Constraints\LengthValidator;

class LengthTest extends \PHPUnit\Framework\TestCase
{
    public function testValidateBy(): void
    {
        $constraint = new Length(['min' => 3]);
        $this->assertEquals(LengthValidator::class, $constraint->validatedBy());
    }
}
