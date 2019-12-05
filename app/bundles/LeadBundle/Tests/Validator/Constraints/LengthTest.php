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

class LengthTest extends \PHPUnit\Framework\TestCase
{
    public function testValidateBy()
    {
        $constraint = new Length(['min' => 3]);
        $this->assertEquals(LengthValidator::class, $constraint->validatedBy());
    }
}
