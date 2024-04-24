<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Field;

use Mautic\LeadBundle\Field\FieldList;
use Mautic\LeadBundle\Field\FieldsWithUniqueIdentifier;
use Mautic\LeadBundle\Field\IdentifierFields;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class IdentifierFieldsTest extends TestCase
{
    /**
     * @var FieldsWithUniqueIdentifier&MockObject
     */
    private MockObject $fieldsWithUniqueIdentifiers;

    /**
     * @var FieldList&MockObject
     */
    private MockObject $fieldList;

    private IdentifierFields $identifierFields;

    protected function setUp(): void
    {
        $this->fieldsWithUniqueIdentifiers = $this->createMock(FieldsWithUniqueIdentifier::class);
        $this->fieldList                   = $this->createMock(FieldList::class);
        $this->identifierFields            = new IdentifierFields($this->fieldsWithUniqueIdentifiers, $this->fieldList);
    }

    public function testLeadObjectReturnsDefaultFields(): void
    {
        $this->fieldsWithUniqueIdentifiers->expects($this->once())
            ->method('getFieldsWithUniqueIdentifier')
            ->with(['object' => 'lead'])
            ->willReturn([]);

        $this->fieldList->expects($this->once())
            ->method('getFieldList')
            ->with(true, false, ['isPublished' => true, 'object' => 'lead'])
            ->willReturn([]);

        $fields = $this->identifierFields->getFieldList('lead');

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

    public function testCompanyObjectReturnsDefaultFields(): void
    {
        $this->fieldsWithUniqueIdentifiers->expects($this->once())
            ->method('getFieldsWithUniqueIdentifier')
            ->with(['object' => 'company'])
            ->willReturn([]);

        $this->fieldList->expects($this->once())
            ->method('getFieldList')
            ->with(true, false, ['isPublished' => true, 'object' => 'company'])
            ->willReturn([]);

        $fields = $this->identifierFields->getFieldList('company');

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

    public function testUniqueIdentifiersAreIncluded(): void
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

        $fields = $this->identifierFields->getFieldList('lead');

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

    public function testSocialFieldsAreIncluded(): void
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

        $fields = $this->identifierFields->getFieldList('lead');

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
}
