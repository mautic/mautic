<?php

/*
 * @copyright   2018 Mautic Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://www.mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Tests\Field;

use Mautic\LeadBundle\Field\FieldList;
use Mautic\LeadBundle\Field\FieldsWithUniqueIdentifier;
use Mautic\LeadBundle\Field\IdentifierFields;

class IdentifierFieldsTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var FieldsWithUniqueIdentifier|\PHPUnit_Framework_MockObject_MockObject
     */
    private $fieldsWithUniqueIdentifiers;

    /**
     * @var FieldList|\PHPUnit_Framework_MockObject_MockObject
     */
    private $fieldList;

    protected function setUp()
    {
        $this->fieldsWithUniqueIdentifiers = $this->createMock(FieldsWithUniqueIdentifier::class);
        $this->fieldList                   = $this->createMock(FieldList::class);
    }

    public function testLeadObjectReturnsDefaultFields()
    {
        $this->fieldsWithUniqueIdentifiers->expects($this->once())
            ->method('getFieldsWithUniqueIdentifier')
            ->with(['object' => 'lead'])
            ->willReturn([]);

        $this->fieldList->expects($this->once())
            ->method('getFieldList')
            ->with(true, false, ['isPublished' => true, 'object' => 'lead'])
            ->willReturn([]);

        $fields = $this->getIdentifierFields()->getFieldList('lead');

        $this->assertEquals(
            [
                'firstname',
                'lastname',
                'company',
                'email',
            ],
            $fields
        );
    }

    public function testCompanyObjectReturnsDefaultFields()
    {
        $this->fieldsWithUniqueIdentifiers->expects($this->once())
            ->method('getFieldsWithUniqueIdentifier')
            ->with(['object' => 'company'])
            ->willReturn([]);

        $this->fieldList->expects($this->once())
            ->method('getFieldList')
            ->with(true, false, ['isPublished' => true, 'object' => 'company'])
            ->willReturn([]);

        $fields = $this->getIdentifierFields()->getFieldList('company');

        $this->assertEquals(
            [
                'companyname',
                'companyemail',
                'companywebsite',
                'city',
                'state',
                'country',
            ],
            $fields
        );
    }

    public function testUniqueIdentifiersAreIncluded()
    {
        $this->fieldsWithUniqueIdentifiers->expects($this->once())
            ->method('getFieldsWithUniqueIdentifier')
            ->with(['object' => 'lead'])
            ->willReturn(
                [
                    'unique_id' => 'Unique ID',
                ]
            );

        $this->fieldList->expects($this->once())
            ->method('getFieldList')
            ->with(true, false, ['isPublished' => true, 'object' => 'lead'])
            ->willReturn([]);

        $fields = $this->getIdentifierFields()->getFieldList('lead');

        $this->assertEquals(
            [
                'firstname',
                'lastname',
                'company',
                'email',
                'unique_id',
            ],
            $fields
        );
    }

    public function testSocialFieldsAreIncluded()
    {
        $this->fieldsWithUniqueIdentifiers->expects($this->once())
            ->method('getFieldsWithUniqueIdentifier')
            ->with(['object' => 'lead'])
            ->willReturn(
                [
                    'unique_id' => 'Unique ID',
                ]
            );

        $this->fieldList->expects($this->once())
            ->method('getFieldList')
            ->with(true, false, ['isPublished' => true, 'object' => 'lead'])
            ->willReturn(
                [
                    'Social' => [
                        'twitter' => [
                            'alias' => 'twitter',
                            'label' => 'Twitter',
                            'type'  => 'text',
                        ],
                    ],
                    'Core' => [
                        'foo' => [
                            'alias' => 'foo',
                            'label' => 'Foo',
                            'type'  => 'text',
                        ],
                    ],
                ]
            );

        $fields = $this->getIdentifierFields()->getFieldList('lead');

        $this->assertEquals(
            [
                'firstname',
                'lastname',
                'company',
                'email',
                'unique_id',
                'twitter',
            ],
            $fields
        );
    }

    /**
     * @return IdentifierFields
     */
    private function getIdentifierFields()
    {
        return new IdentifierFields($this->fieldsWithUniqueIdentifiers, $this->fieldList);
    }
}
