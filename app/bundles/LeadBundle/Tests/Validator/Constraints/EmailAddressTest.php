<?php

namespace Mautic\LeadBundle\Tests\Validator\Constraints;

use Mautic\LeadBundle\Form\Validator\Constraints\EmailAddress;
use Mautic\LeadBundle\Form\Validator\Constraints\EmailAddressValidator;

class EmailAddressTest extends \PHPUnit\Framework\TestCase
{
    public function testValidateBy(): void
    {
        $constraint = new EmailAddress();
        $this->assertEquals(EmailAddressValidator::class, $constraint->validatedBy());
    }
}
