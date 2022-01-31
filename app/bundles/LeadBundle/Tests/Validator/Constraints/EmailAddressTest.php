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
