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

use Mautic\LeadBundle\Entity\CustomFieldRepositoryTrait;

class LeadRepositoryTest extends \PHPUnit_Framework_TestCase
{
    public function testBooleanWithPrepareDbalFieldsForSave()
    {
        $trait  = $this->getMockForTrait(CustomFieldRepositoryTrait::class);
        $fields = [
            'true'   => true,
            'false'  => false,
            'string' => 'blah',
        ];

        $reflection = new \ReflectionObject($trait);
        $method     = $reflection->getMethod('prepareDbalFieldsForSave');
        $method->setAccessible(true);
        $method->invokeArgs($trait, [&$fields]);

        $this->assertEquals(1, $fields['true']);
        $this->assertEquals(0, $fields['false']);
        $this->assertEquals('blah', $fields['string']);
    }
}
