<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Validator\Constraints;

use Mautic\LeadBundle\Form\Validator\Constraints\EmailAddress;
use Mautic\LeadBundle\Form\Validator\Constraints\EmailAddressValidator;

final class EmailAddressTest extends \PHPUnit\Framework\TestCase
{
    public function testValidateBy(): void
    {
        $constraint = new EmailAddress();
        $this->assertSame(EmailAddressValidator::class, $constraint->validatedBy());
    }
}
