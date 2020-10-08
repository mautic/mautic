<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Tests\Entity;

use Mautic\LeadBundle\Entity\Company;

class CompanyTest extends \PHPUnit\Framework\TestCase
{
    public function testChangingPropertiesHydratesFieldChanges()
    {
        $email    = 'foo@bar.com';
        $company  = new Company();
        $company->addUpdatedField('email', $email);
        $changes = $company->getChanges();

        $this->assertFalse(empty($changes['fields']['email']));

        $this->assertEquals($email, $changes['fields']['email'][1]);
    }
}
