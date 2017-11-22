<?php

namespace Mautic\LeadBundle\Tests\Model;

use Mautic\LeadBundle\Model\FieldModel;
use Mautic\LeadBundle\Model\LeadModel;

class LeadModelTest extends \PHPUnit_Framework_TestCase
{
    public function testCheckForDuplicateContact()
    {
        $mockFieldModel = $this->getMockBuilder(FieldModel::class)
            ->disableOriginalConstructor()
            ->setMethods(['getFieldList', 'getUniqueIdentifierFields'])
            ->getMock();

        $mockFieldModel->expects($this->exactly(2))
            ->method('getFieldList')
            ->withConsecutive(
                [false, false, ['isPublished' => true]],
                [false, false, ['isPublished' => true, 'isPubliclyUpdatable' => true]]
            )
            ->will($this->onConsecutiveCalls(
                ['a' => 1],
                ['a' => 3]
            ));

        $mockFieldModel->expects($this->exactly(2))
            ->method('getUniqueIdentifierFields')
            ->will($this->onConsecutiveCalls(
                ['b' => 2],
                ['b' => 4]
            ));

        $mockLeadModel = $this->getMockBuilder(LeadModel::class)
            ->disableOriginalConstructor()
            ->setMethods(null)
            ->getMock();

        $reflectedProp = new \ReflectionProperty(LeadModel::class, 'leadFieldModel');

        $reflectedProp->setAccessible(true);
        $reflectedProp->setValue($mockLeadModel, $mockFieldModel);

        $this->assertAttributeEquals(
            [],
            'availableLeadFields',
            $mockLeadModel,
            'The availableLeadFields property should start empty'
        );

        $mockLeadModel->checkForDuplicateContact([]);
        $this->assertAttributeEquals(['a' => 1], 'availableLeadFields', $mockLeadModel);

        $reflectedProp = new \ReflectionProperty(LeadModel::class, 'availableLeadFields');
        $reflectedProp->setAccessible(true);
        $reflectedProp->setValue($mockLeadModel, []);

        $mockLeadModel->checkForDuplicateContact([], null, false, true);
        $this->assertAttributeEquals(['a' => 3], 'availableLeadFields', $mockLeadModel);
    }
}
