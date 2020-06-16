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

use Mautic\CoreBundle\Test\Doctrine\DBALMocker;
use Mautic\LeadBundle\Entity\CustomFieldRepositoryTrait;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadRepository;

class LeadRepositoryTest extends \PHPUnit\Framework\TestCase
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
     * Ensure that the emails are bound separately as parameters according to
     * https://www.doctrine-project.org/projects/doctrine-orm/en/2.6/reference/query-builder.html#line-number-0a267d5a2c69797a7656aae33fcc140d16b0a566-72.
     */
    public function testBuildQueryForGetLeadsByFieldValue()
    {
        defined('MAUTIC_TABLE_PREFIX') or define('MAUTIC_TABLE_PREFIX', 'mtc_');

        $dbalMock = new DBALMocker($this);

        $mock = $this->getMockBuilder(LeadRepository::class)
            ->disableOriginalConstructor()
            ->setMethods(['getEntityManager'])
            ->getMock();

        $mock->method('getEntityManager')
            ->will($this->returnValue($dbalMock->getMockEm()));

        $reflection = new \ReflectionClass(LeadRepository::class);
        $refMethod  = $reflection->getMethod('buildQueryForGetLeadsByFieldValue');
        $refMethod->setAccessible(true);

        $refMethod->invoke($mock, 'email', ['test@example.com', 'test2@example.com']);

        $parameters = $dbalMock->getQueryPart('parameters');

        $this->assertCount(2, $parameters, 'There should be two parameters bound because that\'s the number of emails we passed into the method.');
    }

    /**
     * Ensure that the array_combine return value matches the old style.
     */
    public function testGetLeadsByFieldValueArrayMapReturn()
    {
        $mock = $this->getMockBuilder(LeadRepository::class)
            ->disableOriginalConstructor()
            ->setMethods(['getEntities', 'buildQueryForGetLeadsByFieldValue'])
            ->getMock();

        // Mock the
        $mockEntity = $this->getMockBuilder(Lead::class)
            ->disableOriginalConstructor()
            ->setMethods(['loadMetadata'])
            ->getMock();

        $mockEntity->setEmail('test@example.com');

        $mockEntity2 = clone $mockEntity;
        $mockEntity2->setEmail('test2@example.com');

        $entities = [
            $mockEntity,
            $mockEntity2,
        ];

        $mock->method('getEntities')
            ->will($this->returnValue($entities));

        $mock->method('buildQueryForGetLeadsByFieldValue')
            ->will($this->returnValue(null));

        $contacts = $mock->getLeadsByFieldValue('email', ['test@example.com', 'test2@example.com']);

        $this->assertSame($entities, $contacts, 'When getting leads without indexing by column, it should match the expected result.');

        $contacts = $mock->getLeadsByFieldValue('email', ['test@example.com', 'test2@example.com'], null, true);

        $expected = [
            'test@example.com',
            'test2@example.com',
        ];

        $this->assertSame($expected, array_keys($contacts), 'When getting leads with indexing by column, it should match the expected result.');
    }
}
