<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Tests\Validator\Constraints;

use Mautic\LeadBundle\Validator\Constraints\Length;
use Mautic\LeadBundle\Validator\Constraints\LengthValidator;

class LengthValidatorTest extends \PHPUnit\Framework\TestCase
{
    public function testValidate()
    {
        $constraint = new Length(['min' => 3]);
        $validator  = new LengthValidator();
        $this->assertNull($validator->validate('valid', $constraint));
        // Not thrownig Symfony\Component\Validator\Exception\UnexpectedTypeException
        $this->assertNull($validator->validate(['0', '1'], $constraint));
    }
}
