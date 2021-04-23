<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Model;

use Doctrine\ORM\EntityManager;
use Mautic\CategoryBundle\Model\CategoryModel;
use Mautic\ChannelBundle\Helper\ChannelListHelper;
use Mautic\CoreBundle\Entity\IpAddress;
use Mautic\CoreBundle\Helper\CookieHelper;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\IpLookupHelper;
use Mautic\CoreBundle\Helper\PathsHelper;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\EmailBundle\Helper\EmailValidator;
use Mautic\LeadBundle\DataObject\LeadManipulator;
use Mautic\LeadBundle\Entity\CompanyLeadRepository;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadEventLog;
use Mautic\LeadBundle\Entity\LeadRepository;
use Mautic\LeadBundle\Entity\StagesChangeLogRepository;
use Mautic\LeadBundle\Exception\ImportFailedException;
use Mautic\LeadBundle\Model\CompanyModel;
use Mautic\LeadBundle\Model\FieldModel;
use Mautic\LeadBundle\Model\IpAddressModel;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\LeadBundle\Model\LegacyLeadModel;
use Mautic\LeadBundle\Model\ListModel;
use Mautic\LeadBundle\Tracker\ContactTracker;
use Mautic\LeadBundle\Tracker\DeviceTracker;
use Mautic\PluginBundle\Helper\IntegrationHelper;
use Mautic\StageBundle\Entity\Stage;
use Mautic\StageBundle\Entity\StageRepository;
use Mautic\UserBundle\Entity\User;
use Mautic\UserBundle\Security\Provider\UserProvider;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Translation\TranslatorInterface;

class LeadModelTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var MockObject|RequestStack
     */
    private $requestStackMock;

    /**
     * @var MockObject|CookieHelper
     */
    private $cookieHelperMock;

    /**
     * @var MockObject|IpLookupHelper
     */
    private $ipLookupHelperMock;

    /**
     * @var MockObject|PathsHelper
     */
    private $pathsHelperMock;

    /**
     * @var MockObject|IntegrationHelper
     */
    private $integrationHelperkMock;

    /**
     * @var MockObject|FieldModel
     */
    private $fieldModelMock;

    /**
     * @var MockObject|ListModel
     */
    private $listModelMock;

    /**
     * @var MockObject|FormFactory
     */
    private $formFactoryMock;

    /**
     * @var MockObject|CompanyModel
     */
    private $companyModelMock;

    /**
     * @var MockObject|CategoryModel
     */
    private $categoryModelMock;

    /**
     * @var MockObject|ChannelListHelper
     */
    private $channelListHelperMock;

    /**
     * @var MockObject|CoreParametersHelper
     */
    private $coreParametersHelperMock;

    /**
     * @var MockObject|EmailValidator
     */
    private $emailValidatorMock;

    /**
     * @var MockObject|UserProvider
     */
    private $userProviderMock;

    /**
     * @var MockObject|ContactTracker
     */
    private $contactTrackerMock;

    /**
     * @var MockObject|DeviceTracker
     */
    private $deviceTrackerMock;

    /**
     * @var MockObject|LegacyLeadModel
     */
    private $legacyLeadModelMock;

    /**
     * @var MockObject|IpAddressModel
     */
    private $ipAddressModelMock;

    /**
     * @var MockObject|LeadRepository
     */
    private $leadRepositoryMock;

    /**
     * @var MockObject|CompanyLeadRepository
     */
    private $companyLeadRepositoryMock;

    /**
     * @var MockObject|UserHelper
     */
    private $userHelperMock;

    /**
     * @var MockObject|EventDispatcherInterface
     */
    private $dispatcherMock;

    /**
     * @var MockObject|EntityManager
     */
    private $entityManagerMock;

    /**
     * @var LeadModel
     */
    private $leadModel;

    protected function setUp(): void
    {
        parent::setUp();

        $this->requestStackMock          = $this->createMock(RequestStack::class);
        $this->cookieHelperMock          = $this->createMock(CookieHelper::class);
        $this->ipLookupHelperMock        = $this->createMock(IpLookupHelper::class);
        $this->pathsHelperMock           = $this->createMock(PathsHelper::class);
        $this->integrationHelperkMock    = $this->createMock(IntegrationHelper::class);
        $this->fieldModelMock            = $this->createMock(FieldModel::class);
        $this->listModelMock             = $this->createMock(ListModel::class);
        $this->formFactoryMock           = $this->createMock(FormFactory::class);
        $this->companyModelMock          = $this->createMock(CompanyModel::class);
        $this->categoryModelMock         = $this->createMock(CategoryModel::class);
        $this->channelListHelperMock     = $this->createMock(ChannelListHelper::class);
        $this->coreParametersHelperMock  = $this->createMock(CoreParametersHelper::class);
        $this->emailValidatorMock        = $this->createMock(EmailValidator::class);
        $this->userProviderMock          = $this->createMock(UserProvider::class);
        $this->contactTrackerMock        = $this->createMock(ContactTracker::class);
        $this->deviceTrackerMock         = $this->createMock(DeviceTracker::class);
        $this->legacyLeadModelMock       = $this->createMock(LegacyLeadModel::class);
        $this->ipAddressModelMock        = $this->createMock(IpAddressModel::class);
        $this->leadRepositoryMock        = $this->createMock(LeadRepository::class);
        $this->companyLeadRepositoryMock = $this->createMock(CompanyLeadRepository::class);
        $this->userHelperMock            = $this->createMock(UserHelper::class);
        $this->dispatcherMock            = $this->createMock(EventDispatcherInterface::class);
        $this->entityManagerMock         = $this->createMock(EntityManager::class);
        $this->leadModel                 = new LeadModel(
            $this->requestStackMock,
            $this->cookieHelperMock,
            $this->ipLookupHelperMock,
            $this->pathsHelperMock,
            $this->integrationHelperkMock,
            $this->fieldModelMock,
            $this->listModelMock,
            $this->formFactoryMock,
            $this->companyModelMock,
            $this->categoryModelMock,
            $this->channelListHelperMock,
            $this->coreParametersHelperMock,
            $this->emailValidatorMock,
            $this->userProviderMock,
            $this->contactTrackerMock,
            $this->deviceTrackerMock,
            $this->legacyLeadModelMock,
            $this->ipAddressModelMock
        );

        $this->leadModel->setUserHelper($this->userHelperMock);
        $this->leadModel->setDispatcher($this->dispatcherMock);
        $this->leadModel->setEntityManager($this->entityManagerMock);

        $this->companyModelMock->method('getCompanyLeadRepository')->willReturn($this->companyLeadRepositoryMock);
    }

    public function testIpLookupDoesNotAddCompanyIfConfiguredSo(): void
    {
        $this->mockGetLeadRepository();

        $entity    = new Lead();
        $ipAddress = new IpAddress();

        $ipAddress->setIpDetails(['organization' => 'Doctors Without Borders']);

        $entity->addIpAddress($ipAddress);

        $this->coreParametersHelperMock->expects($this->exactly(2))
            ->method('get')
            ->withConsecutive(
                ['anonymize_ip', false],
                ['ip_lookup_create_organization', false]
            )
            ->willReturn(false);

        $this->fieldModelMock->method('getFieldListWithProperties')->willReturn([]);
        $this->fieldModelMock->method('getFieldList')->willReturn([]);
        $this->companyLeadRepositoryMock->expects($this->never())->method('getEntitiesByLead');
        $this->companyModelMock->expects($this->never())->method('getEntities');

        $this->leadModel->saveEntity($entity);

        $this->assertNull($entity->getCompany());
        $this->assertTrue(empty($entity->getUpdatedFields()['company']));
    }

    public function testIpLookupAddsCompanyIfDoesNotExistInEntity(): void
    {
        $this->mockGetLeadRepository();

        $companyFromIpLookup = 'Doctors Without Borders';
        $entity              = new Lead();
        $ipAddress           = new IpAddress();

        $ipAddress->setIpDetails(['organization' => $companyFromIpLookup]);

        $entity->addIpAddress($ipAddress);

        $this->coreParametersHelperMock->expects($this->exactly(2))
            ->method('get')
            ->withConsecutive(
                ['anonymize_ip', false],
                ['ip_lookup_create_organization', false]
            )
            ->willReturnOnConsecutiveCalls(false, true);

        $this->fieldModelMock->method('getFieldListWithProperties')->willReturn([]);
        $this->fieldModelMock->method('getFieldList')->willReturn([]);
        $this->companyLeadRepositoryMock->method('getEntitiesByLead')->willReturn([]);
        $this->companyModelMock->expects($this->once())->method('getEntities')->willReturn([]);

        $this->leadModel->saveEntity($entity);

        $this->assertSame($companyFromIpLookup, $entity->getCompany());
        $this->assertSame($companyFromIpLookup, $entity->getUpdatedFields()['company']);
    }

    public function testIpLookupAddsCompanyIfExistsInEntity(): void
    {
        $this->mockGetLeadRepository();

        $companyFromIpLookup = 'Doctors Without Borders';
        $companyFromEntity   = 'Red Cross';
        $entity              = new Lead();
        $ipAddress           = new IpAddress();

        $entity->setCompany($companyFromEntity);
        $ipAddress->setIpDetails(['organization' => $companyFromIpLookup]);

        $entity->addIpAddress($ipAddress);

        $this->coreParametersHelperMock->expects($this->once())->method('get')->with('anonymize_ip', false)->willReturn(false);
        $this->fieldModelMock->method('getFieldListWithProperties')->willReturn([]);
        $this->fieldModelMock->method('getFieldList')->willReturn([]);
        $this->companyLeadRepositoryMock->method('getEntitiesByLead')->willReturn([]);

        $this->leadModel->saveEntity($entity);

        $this->assertSame($companyFromEntity, $entity->getCompany());
        $this->assertFalse(isset($entity->getUpdatedFields()['company']));
    }

    public function testCheckForDuplicateContact(): void
    {
        $this->fieldModelMock->expects($this->once())
            ->method('getFieldList')
            ->with(false, false, ['isPublished' => true, 'object' => 'lead'])
            ->willReturn(['email' => 'Email', 'firstname' => 'First Name']);

        $this->fieldModelMock->expects($this->once())
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

        // The availableLeadFields property should start empty.
        $this->assertEquals([], $mockLeadModel->getAvailableLeadFields());

        $contact = $mockLeadModel->checkForDuplicateContact(['email' => 'john@doe.com', 'firstname' => 'John']);
        $this->assertEquals(['email' => 'Email', 'firstname' => 'First Name'], $mockLeadModel->getAvailableLeadFields());
        $this->assertEquals('john@doe.com', $contact->getEmail());
        $this->assertEquals('John', $contact->getFirstname());
    }

    public function testCheckForDuplicateContactForOnlyPubliclyUpdatable(): void
    {
        $this->fieldModelMock->expects($this->once())
            ->method('getFieldList')
            ->with(false, false, ['isPublished' => true, 'object' => 'lead', 'isPubliclyUpdatable' => true])
            ->willReturn(['email' => 'Email']);

        $this->fieldModelMock->expects($this->once())
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

        // The availableLeadFields property should start empty.
        $this->assertEquals([], $mockLeadModel->getAvailableLeadFields());

        list($contact, $fields) = $mockLeadModel->checkForDuplicateContact(['email' => 'john@doe.com', 'firstname' => 'John'], null, true, true);
        $this->assertEquals(['email' => 'Email'], $mockLeadModel->getAvailableLeadFields());
        $this->assertEquals('john@doe.com', $contact->getEmail());
        $this->assertNull($contact->getFirstname());
        $this->assertEquals(['email' => 'john@doe.com'], $fields);
    }

    /**
     * Test that the Lead won't be set to the LeadEventLog if the Lead save fails.
     */
    public function testImportWillNotSetLeadToLeadEventLogWhenLeadSaveFails(): void
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

    public function testSetFieldValuesWithStage(): void
    {
        $lead = new Lead();
        $lead->setId(1);
        $lead->setFields(['all' => 'sth']);
        $stageMock = $this->createMock(Stage::class);
        $stageMock->expects($this->any())
            ->method('getId')
            ->willReturn(1);
        $data = ['stage' => $stageMock];

        $stagesChangeLogRepo = $this->createMock(StagesChangeLogRepository::class);
        $stagesChangeLogRepo->expects($this->once())
            ->method('getCurrentLeadStage')
            ->with($lead->getId())
            ->willReturn(null);

        $stageRepositoryMock = $this->createMock(StageRepository::class);
        $stageRepositoryMock->expects($this->once())
            ->method('findByIdOrName')
            ->with(1)
            ->willReturn($stageMock);

        $this->entityManagerMock->expects($this->exactly(2))
            ->method('getRepository')
            ->withConsecutive(
                ['MauticLeadBundle:StagesChangeLog'],
                [Stage::class]
            )
            ->willReturnOnConsecutiveCalls(
                $stagesChangeLogRepo,
                $stageRepositoryMock
            );

        $translator = $this->createMock(TranslatorInterface::class);
        $translator->expects($this->once())
            ->method('trans')
            ->with('mautic.stage.event.changed');
        $this->leadModel->setTranslator($translator);

        $this->leadModel->setFieldValues($lead, $data, false, false);
    }

    public function testImportIsIgnoringContactWithNotFoundStage(): void
    {
        $lead = new Lead();
        $lead->setId(1);
        $data = ['stage' => 'not found'];

        $stagesChangeLogRepo = $this->createMock(StagesChangeLogRepository::class);
        $stagesChangeLogRepo->expects($this->once())
            ->method('getCurrentLeadStage')
            ->with($lead->getId())
            ->willReturn(null);

        $stageRepositoryMock = $this->createMock(StageRepository::class);
        $stageRepositoryMock->expects($this->once())
            ->method('findByIdOrName')
            ->with($data['stage'])
            ->willReturn(null);

        $this->entityManagerMock->expects($this->exactly(2))
            ->method('getRepository')
            ->withConsecutive(
                ['MauticLeadBundle:StagesChangeLog'],
                [Stage::class]
            )
            ->willReturnOnConsecutiveCalls(
                $stagesChangeLogRepo,
                $stageRepositoryMock
            );

        $translator = $this->createMock(TranslatorInterface::class);
        $translator->expects($this->once())
            ->method('trans')
            ->with('mautic.lead.import.stage.not.exists', ['id' => $data['stage']])
            ->willReturn('Stage not found');
        $this->leadModel->setTranslator($translator);

        $this->expectException(ImportFailedException::class);

        $this->leadModel->setFieldValues($lead, $data, false, false);
    }

    public function testManipulatorIsLoggedOnlyOnce(): void
    {
        $this->mockGetLeadRepository();

        $contact     = $this->createMock(Lead::class);
        $manipulator = new LeadManipulator('lead', 'import', 333);

        $contact->expects($this->exactly(2))
            ->method('getIpAddresses')
            ->willReturn([]);

        $contact->expects($this->exactly(2))
            ->method('isNewlyCreated')
            ->willReturn(true);

        $contact->expects($this->exactly(2))
            ->method('getManipulator')
            ->willReturn($manipulator);

        $contact->expects($this->once())
            ->method('addEventLog')
            ->with($this->callback(function (LeadEventLog $leadEventLog) use ($contact) {
                $this->assertSame($contact, $leadEventLog->getLead());
                $this->assertSame('identified_contact', $leadEventLog->getAction());
                $this->assertSame('lead', $leadEventLog->getBundle());
                $this->assertSame('import', $leadEventLog->getObject());
                $this->assertSame(333, $leadEventLog->getObjectId());

                return true;
            }));

        $this->fieldModelMock->expects($this->exactly(2))
            ->method('getFieldListWithProperties')
            ->willReturn([]);

        $this->fieldModelMock->expects($this->once())
            ->method('getFieldList')
            ->willReturn([]);

        $this->leadModel->saveEntity($contact);
        $this->leadModel->saveEntity($contact);
    }

    /**
     * Set protected property to an object.
     *
     * @param object $object
     * @param string $class
     * @param string $property
     * @param mixed  $value
     */
    private function setProperty($object, $class, $property, $value): void
    {
        $reflectedProp = new \ReflectionProperty($class, $property);
        $reflectedProp->setAccessible(true);
        $reflectedProp->setValue($object, $value);
    }

    private function mockGetLeadRepository()
    {
        $this->entityManagerMock->expects($this->any())
            ->method('getRepository')
            ->will(
                $this->returnValueMap(
                    [
                        [Lead::class, $this->leadRepositoryMock],
                    ]
                )
            );
    }
}
