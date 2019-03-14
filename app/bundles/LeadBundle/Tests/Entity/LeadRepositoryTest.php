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
use Mautic\LeadBundle\Entity\LeadRepository;
use Mautic\LeadBundle\Tests\Stubs\QueryBuilder as QueryBuilderStub;

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

    /**
     * Ensure that the emails are bound separately
     * as parameters according to https://www.doctrine-project.org/projects/doctrine-orm/en/2.6/reference/query-builder.html#line-number-0a267d5a2c69797a7656aae33fcc140d16b0a566-72.
     */
    public function testBuildQueryForGetLeadsByFieldValue()
    {
        defined('MAUTIC_TABLE_PREFIX') or define('MAUTIC_TABLE_PREFIX', 'mtc_');

        $mock = $this->getMockBuilder(LeadRepository::class)
            ->disableOriginalConstructor()
            ->setMethods(['createQueryBuilderFromConnection', 'generateRandomParameterName'])
            ->getMock();

        $mock->method('createQueryBuilderFromConnection')
            ->will($this->returnValue($queryBuilder = new QueryBuilderStub()));

        $mock->method('generateRandomParameterName')
            ->will($this->onConsecutiveCalls('a', 'b'));

        $reflection = new \ReflectionClass(LeadRepository::class);
        $refMethod  = $reflection->getMethod('buildQueryForGetLeadsByFieldValue');
        $refMethod->setAccessible(true);

        $query = $refMethod->invoke($mock, 'email', ['test@example.com', 'test2@example.com']);

        $this->assertCount(2, $query->getParameters(), 'There should be two parameters bound because that\'s the number of emails we passed into the method.');
    }
}
