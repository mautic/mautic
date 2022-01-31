<?php
/*
 * @copyright   2021 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Tests\Validator\Constraints;

use Mautic\EmailBundle\Helper\EmailValidator;
use Mautic\LeadBundle\Form\Validator\Constraints\EmailAddress;
use Mautic\LeadBundle\Form\Validator\Constraints\EmailAddressValidator;

class EmailAddressValidatorTest extends \PHPUnit\Framework\TestCase
{
    public function testValidate(): void
    {
        $constraint            = new EmailAddress();
        $emailAddressValidator = $this->createMock(EmailValidator::class);
        $emailAddressValidator->method('validate')->willReturn(null);
        $validator  = new EmailAddressValidator($emailAddressValidator);
        $validator->validate('test@test.com', $constraint);
    }
}
