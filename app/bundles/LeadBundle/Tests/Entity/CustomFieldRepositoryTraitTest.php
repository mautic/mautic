<?php

namespace Mautic\LeadBundle\Tests\Entity;

use Mautic\LeadBundle\Entity\LeadRepository;
use Mautic\LeadBundle\Tests\StandardImportTestHelper;

class CustomFieldRepositoryTraitTest extends StandardImportTestHelper
{
    private $fields = [
        'firstname' => [
            'id'       => 2,
            'label'    => 'First Name',
            'alias'    => 'firstname',
            'type'     => 'text',
            'group'    => 'core',
            'object'   => 'lead',
            'is_fixed' => 1,
        ],
        'lastname' => [
            'id'       => 3,
            'label'    => 'Last Name',
            'alias'    => 'lastname',
            'type'     => 'text',
            'group'    => 'core',
            'object'   => 'lead',
            'is_fixed' => 1,
        ],
        'twitter' => [
            'id'       => 27,
            'label'    => 'Twitter',
            'alias'    => 'twitter',
            'type'     => 'text',
            'group'    => 'social',
            'object'   => 'lead',
            'is_fixed' => 0,
        ],
    ];

    private $fieldValues = [
        'preferred_profile_image' => 'gravatar',
        'firstname'               => 'John',
        'lastname'                => 'Doe',
        'twitter'                 => 'johndoe',
    ];

    protected $fixedFields = [
        'firstname' => 'firstname',
        'lastname'  => 'lastname',
    ];

    protected $baseColumns = [
        'preferred_profile_image',
        'firstname',
        'lastname',
    ];

    protected $fieldGroups = [
        'core',
        'social',
        'personal',
        'professional',
    ];

    public function testFormatFieldValues(): void
    {
        $mockWithTrait = $this->getMockBuilder(LeadRepository::class)
            ->disableOriginalConstructor()
            ->enableOriginalClone()
            ->onlyMethods(['getCustomFieldList', 'getBaseColumns', 'getClassName', 'getFieldGroups'])
            ->getMock();
        $mockWithTrait->method('getCustomFieldList')
            ->willReturn([$this->fields, $this->fixedFields]);

        $mockWithTrait->method('getBaseColumns')
            ->willReturn($this->baseColumns);

        $mockWithTrait->method('getClassName')
            ->willReturn(\Mautic\LeadBundle\Entity\Lead::class);

        $mockWithTrait->method('getFieldGroups')
            ->willReturn($this->fieldGroups);

        $reflectedMockTrait = new \ReflectionObject($mockWithTrait);
        $method             = $reflectedMockTrait->getMethod('formatFieldValues');
        $method->setAccessible(true);

        $expected = [
            'core' => [
                'firstname' => [
                    'id'       => 2,
                    'label'    => 'First Name',
                    'alias'    => 'firstname',
                    'type'     => 'text',
                    'group'    => 'core',
                    'object'   => 'lead',
                    'is_fixed' => 1,
                    'value'    => 'John',
                ],
                'lastname' => [
                    'id'       => 3,
                    'label'    => 'Last Name',
                    'alias'    => 'lastname',
                    'type'     => 'text',
                    'group'    => 'core',
                    'object'   => 'lead',
                    'is_fixed' => 1,
                    'value'    => 'Doe',
                ],
            ],
            'social' => [
                'twitter' => [
                    'id'       => 27,
                    'label'    => 'Twitter',
                    'alias'    => 'twitter',
                    'type'     => 'text',
                    'group'    => 'social',
                    'object'   => 'lead',
                    'is_fixed' => 0,
                    'value'    => 'johndoe',
                ],
            ],
            'personal'     => [],
            'professional' => [],
        ];

        $result = $method->invokeArgs($mockWithTrait, [$this->fieldValues]);
        $this->assertSame($expected, $result);
    }

    public function testFormatFieldValuesWhenAFieldIsUnpublished(): void
    {
        $mockWithTrait = $this->getMockBuilder(LeadRepository::class)
            ->disableOriginalConstructor()
            ->enableOriginalClone()
            ->onlyMethods(['getCustomFieldList', 'getBaseColumns', 'getClassName', 'getFieldGroups'])
            ->getMock();

        $mockWithTrait->method('getCustomFieldList')
            ->willReturn([$this->fields, $this->fixedFields]);

        $mockWithTrait->method('getBaseColumns')
            ->willReturn($this->baseColumns);

        $mockWithTrait->method('getClassName')
            ->willReturn(\Mautic\LeadBundle\Entity\Lead::class);

        $mockWithTrait->method('getFieldGroups')
            ->willReturn($this->fieldGroups);

        $reflectedMockTrait = new \ReflectionObject($mockWithTrait);
        $method             = $reflectedMockTrait->getMethod('formatFieldValues');
        $method->setAccessible(true);

        $expected = [
            'core' => [
                'firstname' => [
                    'id'       => 2,
                    'label'    => 'First Name',
                    'alias'    => 'firstname',
                    'type'     => 'text',
                    'group'    => 'core',
                    'object'   => 'lead',
                    'is_fixed' => 1,
                    'value'    => 'John',
                ],
                'lastname' => [
                    'id'       => 3,
                    'label'    => 'Last Name',
                    'alias'    => 'lastname',
                    'type'     => 'text',
                    'group'    => 'core',
                    'object'   => 'lead',
                    'is_fixed' => 1,
                    'value'    => 1,
                ],
            ],
            'social' => [
                'twitter' => [
                    'id'       => 27,
                    'label'    => 'Twitter',
                    'alias'    => 'twitter',
                    'type'     => 'text',
                    'group'    => 'social',
                    'object'   => 'lead',
                    'is_fixed' => 0,
                    'value'    => 'johndoe',
                ],
            ],
            'personal'     => [],
            'professional' => [],
        ];

        $values = $this->fieldValues;

        // Simulate unpublished field:
        unset($values['lastname']);

        $result = $method->invokeArgs($mockWithTrait, [$values]);
        $this->assertEquals($expected, $result);
    }
}
