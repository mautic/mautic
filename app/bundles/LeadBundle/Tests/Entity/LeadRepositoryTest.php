<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Tests;

use Mautic\LeadBundle\Entity\CustomFieldRepositoryTrait;

class LeadRepositoryTest extends \PHPUnit_Framework_TestCase
{
    use CustomFieldRepositoryTrait;

    public function testBooleanWithPrepareDbalFieldsForSave()
    {
        $fields = [
            'true'   => true,
            'false'  => false,
            'string' => 'blah',
        ];

        $this->prepareDbalFieldsForSave($fields);

        $this->assertEquals(1, $fields['true']);
        $this->assertEquals(0, $fields['false']);
        $this->assertEquals('blah', $fields['string']);
    }
}
