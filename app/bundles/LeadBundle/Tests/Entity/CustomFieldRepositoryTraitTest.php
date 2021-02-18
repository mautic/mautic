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

    public function testFormatFieldValues()
    {
        $mockTrait = $this->getMockForTrait(CustomFieldRepositoryTrait::class, [], '', false, true, true, ['getCustomFieldList', 'getBaseColumns', 'getClassName', 'getFieldGroups']);
        $mockTrait->method('getCustomFieldList')
            ->will($this->returnValue([$this->fields, $this->fixedFields]));

        $mockTrait->method('getBaseColumns')
            ->will($this->returnValue($this->baseColumns));

        $mockTrait->method('getClassName')
            ->will($this->returnValue('Mautic\LeadBundle\Entity\Lead'));

        $mockTrait->method('getFieldGroups')
            ->will($this->returnValue($this->fieldGroups));

        $reflectedMockTrait = new \ReflectionObject($mockTrait);
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

        $result = $method->invokeArgs($mockTrait, [$this->fieldValues]);
        $this->assertSame($expected, $result);
    }

    public function testFormatFieldValuesWhenAFieldIsUnpublished()
    {
        $mockTrait = $this->getMockForTrait(CustomFieldRepositoryTrait::class, [], '', false, true, true, ['getCustomFieldList', 'getBaseColumns', 'getClassName', 'getFieldGroups']);
        $mockTrait->method('getCustomFieldList')
            ->will($this->returnValue([$this->fields, $this->fixedFields]));

        $mockTrait->method('getBaseColumns')
            ->will($this->returnValue($this->baseColumns));

        $mockTrait->method('getClassName')
            ->will($this->returnValue('Mautic\LeadBundle\Entity\Lead'));

        $mockTrait->method('getFieldGroups')
            ->will($this->returnValue($this->fieldGroups));

        $reflectedMockTrait = new \ReflectionObject($mockTrait);
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

        $result = $method->invokeArgs($mockTrait, [$values]);
        $this->assertEquals($expected, $result);
    }
}
