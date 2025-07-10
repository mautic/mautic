<?php

namespace Mautic\LeadBundle\Tests\Validator\Constraints;

use Mautic\LeadBundle\Validator\Constraints\Length;
use Mautic\LeadBundle\Validator\Constraints\LengthValidator;

class LengthValidatorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @doesNotPerformAssertions
     */
    public function testValidate(): void
    {
        $constraint = new Length(['min' => 3]);
        $validator  = new LengthValidator();

        $validator->validate('valid', $constraint);
        // Not thrownig Symfony\Component\Validator\Exception\UnexpectedTypeException
        $validator->validate(['0', '1'], $constraint);
    }
}
