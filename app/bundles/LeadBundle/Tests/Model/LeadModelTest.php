<?php

namespace Mautic\LeadBundle\Tests\Model;

use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadEventLog;
use Mautic\LeadBundle\Model\CompanyModel;
use Mautic\LeadBundle\Model\FieldModel;
use Mautic\LeadBundle\Model\LeadModel;

class LeadModelTest extends \PHPUnit_Framework_TestCase
{
    public function testCheckForDuplicateContact()
    {
        $mockFieldModel = $this->getMockBuilder(FieldModel::class)
            ->disableOriginalConstructor()
            ->setMethods(['getFieldList', 'getUniqueIdentifierFields', 'getEntities'])
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

        $mockFieldModel->expects($this->once())
            ->method('getEntities')
            ->willReturn([
                'b' => ['label' => 'b', 'alias' => 'b', 'isPublished' => true, 'id' => 4, 'object' => 'lead', 'group' => 'basic', 'type' => 'text'],
                'a' => ['label' => 'a', 'alias' => 'a', 'isPublished' => true, 'id' => 5, 'object' => 'lead', 'group' => 'basic', 'type' => 'text'],
            ]);

        $mockLeadModel = $this->getMockBuilder(LeadModel::class)
            ->disableOriginalConstructor()
            ->setMethods(null)
            ->getMock();

        $this->setProperty($mockLeadModel, LeadModel::class, 'leadFieldModel', $mockFieldModel);

        $this->assertAttributeEquals(
            [],
            'availableLeadFields',
            $mockLeadModel,
            'The availableLeadFields property should start empty'
        );

        $mockLeadModel->checkForDuplicateContact([]);
        $this->assertAttributeEquals(['a' => 1], 'availableLeadFields', $mockLeadModel);

        $this->setProperty($mockLeadModel, LeadModel::class, 'availableLeadFields', []);

        $mockLeadModel->checkForDuplicateContact([], null, false, true);
        $this->assertAttributeEquals(['a' => 3], 'availableLeadFields', $mockLeadModel);
    }

    /**
     * Test that the Lead won't be set to the LeadEventLog if the Lead save fails.
     */
    public function testImportWillNotSetLeadToLeadEventLogWhenLeadSaveFails()
    {
        $leadEventLog  = new LeadEventLog();
        $mockLeadModel = $this->getMockBuilder(LeadModel::class)
            ->disableOriginalConstructor()
            ->setMethods(['saveEntity', 'checkForDuplicateContact'])
            ->getMock();

        $mockCompanyModel = $this->getMockBuilder(CompanyModel::class)
            ->disableOriginalConstructor()
            ->setMethods(['extractCompanyDataFromImport'])
            ->getMock();

        $mockCompanyModel->expects($this->once())->method('extractCompanyDataFromImport')->willReturn([[], []]);

        $this->setProperty($mockLeadModel, LeadModel::class, 'companyModel', $mockCompanyModel);
        $this->setProperty($mockLeadModel, LeadModel::class, 'leadFields', [['alias' => 'email', 'type' => 'email', 'defaultValue' => '']]);

        $mockLeadModel->expects($this->once())->method('saveEntity')->willThrowException(new \Exception());
        $mockLeadModel->expects($this->once())->method('checkForDuplicateContact')->willReturn(new Lead());

        try {
            $mockLeadModel->import([], [], null, null, null, true, $leadEventLog);
        } catch (\Exception $e) {
            $this->assertNull($leadEventLog->getLead());
        }
    }

    /**
     * Test that the Lead will be set to the LeadEventLog if the Lead save succeed.
     */
    public function testImportWillSetLeadToLeadEventLogWhenLeadSaveSucceed()
    {
        $leadEventLog  = new LeadEventLog();
        $lead          = new Lead();
        $mockLeadModel = $this->getMockBuilder(LeadModel::class)
            ->disableOriginalConstructor()
            ->setMethods(['saveEntity', 'checkForDuplicateContact'])
            ->getMock();

        $mockCompanyModel = $this->getMockBuilder(CompanyModel::class)
            ->disableOriginalConstructor()
            ->setMethods(['extractCompanyDataFromImport'])
            ->getMock();

        $mockCompanyModel->expects($this->once())->method('extractCompanyDataFromImport')->willReturn([[], []]);

        $this->setProperty($mockLeadModel, LeadModel::class, 'companyModel', $mockCompanyModel);
        $this->setProperty($mockLeadModel, LeadModel::class, 'leadFields', [['alias' => 'email', 'type' => 'email', 'defaultValue' => '']]);

        $mockLeadModel->expects($this->once())->method('checkForDuplicateContact')->willReturn($lead);

        try {
            $mockLeadModel->import([], [], null, null, null, true, $leadEventLog);
        } catch (\Exception $e) {
            $this->assertEquals($lead, $leadEventLog->getLead());
        }
    }

    /**
     * Set protected property to an object.
     *
     * @param object $object
     * @param string $class
     * @param string $property
     * @param mixed  $value
     */
    private function setProperty($object, $class, $property, $value)
    {
        $reflectedProp = new \ReflectionProperty($class, $property);
        $reflectedProp->setAccessible(true);
        $reflectedProp->setValue($object, $value);
    }
}
