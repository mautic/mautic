<?php

namespace Mautic\LeadBundle\Tests\Model;

use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadEventLog;
use Mautic\LeadBundle\Entity\LeadRepository;
use Mautic\LeadBundle\Model\CompanyModel;
use Mautic\LeadBundle\Model\FieldModel;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\UserBundle\Entity\User;

class LeadModelTest extends \PHPUnit_Framework_TestCase
{
    private $fieldModelMock;
    private $leadRepositoryMock;

    protected function setUp()
    {
        parent::setUp();
        $this->fieldModelMock     = $this->createMock(FieldModel::class);
        $this->leadRepositoryMock = $this->createMock(LeadRepository::class);
    }

    public function testCheckForDuplicateContact()
    {
        $this->fieldModelMock->expects($this->at(0))
            ->method('getFieldList')
            ->with(false, false, ['isPublished' => true, 'object' => 'lead'])
            ->willReturn(['email' => 'Email', 'firstname' => 'First Name']);

        $this->fieldModelMock->expects($this->at(1))
            ->method('getUniqueIdentifierFields')
            ->willReturn(['email' => 'Email']);

        $this->fieldModelMock->expects($this->once())
            ->method('getEntities')
            ->willReturn([
                4 => ['label' => 'Email', 'alias' => 'email', 'isPublished' => true, 'id' => 4, 'object' => 'lead', 'group' => 'basic', 'type' => 'email'],
                5 => ['label' => 'First Name', 'alias' => 'firstname', 'isPublished' => true, 'id' => 5, 'object' => 'lead', 'group' => 'basic', 'type' => 'text'],
            ]);

        $mockLeadModel = $this->getMockBuilder(LeadModel::class)
            ->disableOriginalConstructor()
            ->setMethods(['getRepository'])
            ->getMock();

        $mockLeadModel->expects($this->once())
            ->method('getRepository')
            ->willReturn($this->leadRepositoryMock);

        $this->leadRepositoryMock->expects($this->once())
            ->method('getLeadsByUniqueFields')
            ->with(['email' => 'john@doe.com'], null)
            ->willReturn([]);

        $this->setProperty($mockLeadModel, LeadModel::class, 'leadFieldModel', $this->fieldModelMock);

        $this->assertAttributeEquals(
            [],
            'availableLeadFields',
            $mockLeadModel,
            'The availableLeadFields property should start empty'
        );

        $contact = $mockLeadModel->checkForDuplicateContact(['email' => 'john@doe.com', 'firstname' => 'John']);
        $this->assertAttributeEquals(['email' => 'Email', 'firstname' => 'First Name'], 'availableLeadFields', $mockLeadModel);
        $this->assertEquals('john@doe.com', $contact->getEmail());
        $this->assertEquals('John', $contact->getFirstname());
    }

    public function testCheckForDuplicateContactForOnlyPubliclyUpdatable()
    {
        $this->fieldModelMock->expects($this->at(0))
            ->method('getFieldList')
            ->with(false, false, ['isPublished' => true, 'object' => 'lead', 'isPubliclyUpdatable' => true])
            ->willReturn(['email' => 'Email']);

        $this->fieldModelMock->expects($this->at(1))
            ->method('getUniqueIdentifierFields')
            ->willReturn(['email' => 'Email']);

        $this->fieldModelMock->expects($this->once())
            ->method('getEntities')
            ->willReturn([
                4 => ['label' => 'Email', 'alias' => 'email', 'isPublished' => true, 'id' => 4, 'object' => 'lead', 'group' => 'basic', 'type' => 'email'],
                5 => ['label' => 'First Name', 'alias' => 'firstname', 'isPublished' => true, 'id' => 5, 'object' => 'lead', 'group' => 'basic', 'type' => 'text'],
            ]);

        $mockLeadModel = $this->getMockBuilder(LeadModel::class)
            ->disableOriginalConstructor()
            ->setMethods(['getRepository'])
            ->getMock();

        $mockLeadModel->expects($this->once())
            ->method('getRepository')
            ->willReturn($this->leadRepositoryMock);

        $this->leadRepositoryMock->expects($this->once())
            ->method('getLeadsByUniqueFields')
            ->with(['email' => 'john@doe.com'], null)
            ->willReturn([]);

        $this->setProperty($mockLeadModel, LeadModel::class, 'leadFieldModel', $this->fieldModelMock);

        $this->assertAttributeEquals(
            [],
            'availableLeadFields',
            $mockLeadModel,
            'The availableLeadFields property should start empty'
        );

        list($contact, $fields) = $mockLeadModel->checkForDuplicateContact(['email' => 'john@doe.com', 'firstname' => 'John'], null, true, true);
        $this->assertAttributeEquals(['email' => 'Email'], 'availableLeadFields', $mockLeadModel);
        $this->assertEquals('john@doe.com', $contact->getEmail());
        $this->assertNull($contact->getFirstname());
        $this->assertEquals(['email' => 'john@doe.com'], $fields);
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

        $mockUserModel = $this->getMockBuilder(UserHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mockUserModel->method('getUser')
            ->willReturn(new User());

        $mockLeadModel->setUserHelper($mockUserModel);

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

        $mockUserModel = $this->getMockBuilder(UserHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mockUserModel->method('getUser')
            ->willReturn(new User());

        $mockLeadModel->setUserHelper($mockUserModel);

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
