<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Model;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Mautic\CategoryBundle\Model\CategoryModel;
use Mautic\ChannelBundle\Helper\ChannelListHelper;
use Mautic\CoreBundle\Entity\IpAddress;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\IpLookupHelper;
use Mautic\CoreBundle\Helper\PathsHelper;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\EmailBundle\Helper\EmailValidator;
use Mautic\LeadBundle\DataObject\LeadManipulator;
use Mautic\LeadBundle\Entity\CompanyLeadRepository;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadEventLog;
use Mautic\LeadBundle\Entity\LeadRepository;
use Mautic\LeadBundle\Entity\StagesChangeLog;
use Mautic\LeadBundle\Entity\StagesChangeLogRepository;
use Mautic\LeadBundle\Event\LeadEvent;
use Mautic\LeadBundle\Event\SaveBatchLeadsEvent;
use Mautic\LeadBundle\Exception\ImportFailedException;
use Mautic\LeadBundle\Field\FieldsWithUniqueIdentifier;
use Mautic\LeadBundle\LeadEvents;
use Mautic\LeadBundle\Model\CompanyModel;
use Mautic\LeadBundle\Model\FieldModel;
use Mautic\LeadBundle\Model\IpAddressModel;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\LeadBundle\Model\ListModel;
use Mautic\LeadBundle\Tests\Fixtures\Model\LeadModelStub;
use Mautic\LeadBundle\Tracker\ContactTracker;
use Mautic\LeadBundle\Tracker\DeviceTracker;
use Mautic\PluginBundle\Helper\IntegrationHelper;
use Mautic\StageBundle\Entity\Stage;
use Mautic\StageBundle\Entity\StageRepository;
use Mautic\UserBundle\Entity\User;
use Mautic\UserBundle\Security\Provider\UserProvider;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class LeadModelTest extends \PHPUnit\Framework\TestCase
{
    private MockObject|RequestStack $requestStack;

    /**
     * @var MockObject|IpLookupHelper
     */
    private MockObject $ipLookupHelperMock;

    /**
     * @var MockObject|PathsHelper
     */
    private MockObject $pathsHelperMock;

    /**
     * @var MockObject|IntegrationHelper
     */
    private MockObject $integrationHelperkMock;

    /**
     * @var MockObject|FieldModel
     */
    private MockObject $fieldModelMock;

    /**
     * @var MockObject&FieldsWithUniqueIdentifier
     */
    private MockObject $fieldsWithUniqueIdentifier;

    /**
     * @var MockObject|ListModel
     */
    private MockObject $listModelMock;

    /**
     * @var MockObject|FormFactory
     */
    private MockObject $formFactoryMock;

    /**
     * @var MockObject|CompanyModel
     */
    private MockObject $companyModelMock;

    /**
     * @var MockObject|CategoryModel
     */
    private MockObject $categoryModelMock;

    private ChannelListHelper $channelListHelperMock;

    /**
     * @var MockObject|CoreParametersHelper
     */
    private MockObject $coreParametersHelperMock;

    /**
     * @var MockObject|EmailValidator
     */
    private MockObject $emailValidatorMock;

    /**
     * @var MockObject|UserProvider
     */
    private MockObject $userProviderMock;

    /**
     * @var MockObject|ContactTracker
     */
    private MockObject $contactTrackerMock;

    /**
     * @var MockObject|DeviceTracker
     */
    private MockObject $deviceTrackerMock;

    /**
     * @var MockObject|IpAddressModel
     */
    private MockObject $ipAddressModelMock;

    /**
     * @var MockObject|LeadRepository
     */
    private MockObject $leadRepositoryMock;

    /**
     * @var MockObject|CompanyLeadRepository
     */
    private MockObject $companyLeadRepositoryMock;

    /**
     * @var MockObject|UserHelper
     */
    private MockObject $userHelperMock;

    /**
     * @var MockObject|EventDispatcherInterface
     */
    private MockObject $dispatcherMock;

    /**
     * @var MockObject|EntityManager
     */
    private MockObject $entityManagerMock;

    private LeadModel $leadModel;

    /**
     * @var MockObject&Translator
     */
    private MockObject $translator;

    /**
     * @var MockObject&UrlGeneratorInterface
     */
    private MockObject $urlGeneratorInterfaceMock;

    /**
     * @var MockObject&LoggerInterface
     */
    private MockObject $logger;

    /**
     * @var MockObject&CorePermissions
     */
    private MockObject $corePermissionsMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->requestStack             = new RequestStack();
        $this->requestStack->push(new Request());
        $this->ipLookupHelperMock               = $this->createMock(IpLookupHelper::class);
        $this->pathsHelperMock                  = $this->createMock(PathsHelper::class);
        $this->integrationHelperkMock           = $this->createMock(IntegrationHelper::class);
        $this->fieldModelMock                   = $this->createMock(FieldModel::class);
        $this->fieldsWithUniqueIdentifier       = $this->createMock(FieldsWithUniqueIdentifier::class);
        $this->listModelMock                    = $this->createMock(ListModel::class);
        $this->formFactoryMock                  = $this->createMock(FormFactory::class);
        $this->companyModelMock                 = $this->createMock(CompanyModel::class);
        $this->categoryModelMock                = $this->createMock(CategoryModel::class);
        $this->channelListHelperMock            = new ChannelListHelper($this->createMock(EventDispatcherInterface::class), $this->createMock(Translator::class));
        $this->coreParametersHelperMock         = $this->createMock(CoreParametersHelper::class);
        $this->emailValidatorMock               = $this->createMock(EmailValidator::class);
        $this->userProviderMock                 = $this->createMock(UserProvider::class);
        $this->contactTrackerMock               = $this->createMock(ContactTracker::class);
        $this->deviceTrackerMock                = $this->createMock(DeviceTracker::class);
        $this->ipAddressModelMock               = $this->createMock(IpAddressModel::class);
        $this->leadRepositoryMock               = $this->createMock(LeadRepository::class);
        $this->companyLeadRepositoryMock        = $this->createMock(CompanyLeadRepository::class);
        $this->userHelperMock                   = $this->createMock(UserHelper::class);
        $this->dispatcherMock                   = $this->createMock(EventDispatcherInterface::class);
        $this->entityManagerMock                = $this->createMock(EntityManager::class);
        $this->corePermissionsMock              = $this->createMock(CorePermissions::class);
        $this->translator                       = $this->createMock(Translator::class);
        $this->urlGeneratorInterfaceMock        = $this->createMock(UrlGeneratorInterface::class);
        $this->logger                           = $this->createMock(LoggerInterface::class);
        $this->leadModel                        = new LeadModel(
            $this->requestStack,
            $this->ipLookupHelperMock,
            $this->pathsHelperMock,
            $this->integrationHelperkMock,
            $this->fieldModelMock,
            $this->fieldsWithUniqueIdentifier,
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
            $this->ipAddressModelMock,
            $this->entityManagerMock,
            $this->corePermissionsMock,
            $this->dispatcherMock,
            $this->urlGeneratorInterfaceMock,
            $this->translator,
            $this->userHelperMock,
            $this->logger,
        );

        $this->setSecurity($this->leadModel);

        $this->companyModelMock->method('getCompanyLeadRepository')->willReturn($this->companyLeadRepositoryMock);
    }

    public function testIpLookupDoesNotAddCompanyIfConfiguredSo(): void
    {
        $this->mockGetLeadRepository();

        $entity    = new Lead();
        $ipAddress = new IpAddress('some.ip');

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
        $ipAddress           = new IpAddress('some.ip');

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
        $this->companyModelMock->expects($this->any())
            ->method('fetchCompanyFields')
            ->willReturn([]);

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
        $ipAddress           = new IpAddress('some.ip');

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

        $this->fieldsWithUniqueIdentifier->expects($this->once())
            ->method('getFieldsWithUniqueIdentifier')
            ->willReturn(['email' => 'Email']);

        $this->fieldModelMock->expects($this->once())
            ->method('getEntities')
            ->willReturn($this->getFieldPaginatorFake());

        $mockLeadModel = $this->getMockBuilder(LeadModel::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getRepository'])
            ->getMock();

        $mockLeadModel->expects($this->once())
            ->method('getRepository')
            ->willReturn($this->leadRepositoryMock);

        $this->leadRepositoryMock->expects($this->once())
            ->method('getLeadsByUniqueFields')
            ->with(['email' => 'john@doe.com'], null)
            ->willReturn([]);

        $this->setProperty($mockLeadModel, LeadModel::class, 'leadFieldModel', $this->fieldModelMock);
        $this->setProperty($mockLeadModel, LeadModel::class, 'fieldsWithUniqueIdentifier', $this->fieldsWithUniqueIdentifier);

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

        $this->fieldsWithUniqueIdentifier->expects($this->once())
            ->method('getFieldsWithUniqueIdentifier')
            ->willReturn(['email' => 'Email']);

        $this->fieldModelMock->expects($this->once())
            ->method('getEntities')
            ->willReturn($this->getFieldPaginatorFake());

        /** @var LeadModel&MockObject $mockLeadModel */
        $mockLeadModel = $this->getMockBuilder(LeadModel::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getRepository'])
            ->getMock();

        $mockLeadModel->expects($this->once())
            ->method('getRepository')
            ->willReturn($this->leadRepositoryMock);

        $this->leadRepositoryMock->expects($this->once())
            ->method('getLeadsByUniqueFields')
            ->with(['email' => 'john@doe.com'], null)
            ->willReturn([]);

        $this->setProperty($mockLeadModel, LeadModel::class, 'leadFieldModel', $this->fieldModelMock);
        $this->setProperty($mockLeadModel, LeadModel::class, 'fieldsWithUniqueIdentifier', $this->fieldsWithUniqueIdentifier);

        // The availableLeadFields property should start empty.
        $this->assertEquals([], $mockLeadModel->getAvailableLeadFields());

        [$contact, $fields] = $mockLeadModel->checkForDuplicateContact(['email' => 'john@doe.com', 'firstname' => 'John'], true, true);
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
        $mockUserModel = $this->getMockBuilder(UserHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mockUserModel->method('getUser')
            ->willReturn(new User());

        $mockLeadModel = $this->getMockBuilder(LeadModelStub::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['saveEntity', 'checkForDuplicateContact'])
            ->getMock();

        $mockLeadModel->setUserHelper($mockUserModel);
        $this->setSecurity($mockLeadModel);

        $mockCompanyModel = $this->getMockBuilder(CompanyModel::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['extractCompanyDataFromImport'])
            ->getMock();

        $mockCompanyModel->expects($this->once())->method('extractCompanyDataFromImport')->willReturn([[], []]);

        $this->setProperty($mockLeadModel, LeadModel::class, 'companyModel', $mockCompanyModel);
        $this->setProperty($mockLeadModel, LeadModel::class, 'leadFields', [['alias' => 'email', 'type' => 'email', 'defaultValue' => '']]);

        $mockLeadModel->expects($this->once())->method('saveEntity')->willThrowException(new \Exception());
        $mockLeadModel->expects($this->once())->method('checkForDuplicateContact')->willReturn(new Lead());

        try {
            $mockLeadModel->import([], [], null, null, null, true, $leadEventLog);
        } catch (\Exception) {
            $this->assertNull($leadEventLog->getLead());
        }
    }

    /**
     * Test that the Lead will be set to the LeadEventLog if the Lead save succeed.
     */
    public function testImportWillSetLeadToLeadEventLogWhenLeadSaveSucceed(): void
    {
        $leadEventLog  = new LeadEventLog();
        $lead          = new Lead();

        $mockUserModel = $this->getMockBuilder(UserHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mockUserModel->method('getUser')
            ->willReturn(new User());

        $mockLeadModel = $this->getMockBuilder(LeadModelStub::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['saveEntity', 'checkForDuplicateContact'])
            ->getMock();

        $mockLeadModel->setUserHelper($mockUserModel);
        $this->setSecurity($mockLeadModel);

        $mockCompanyModel = $this->getMockBuilder(CompanyModel::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['extractCompanyDataFromImport'])
            ->getMock();

        $mockCompanyModel->expects($this->once())->method('extractCompanyDataFromImport')->willReturn([[], []]);

        $this->setProperty($mockLeadModel, LeadModel::class, 'companyModel', $mockCompanyModel);
        $this->setProperty($mockLeadModel, LeadModel::class, 'leadFields', [['alias' => 'email', 'type' => 'email', 'defaultValue' => '']]);

        $mockLeadModel->expects($this->once())->method('checkForDuplicateContact')->willReturn($lead);

        try {
            $mockLeadModel->import([], [], null, null, null, true, $leadEventLog);
        } catch (\Exception) {
            $this->assertEquals($lead, $leadEventLog->getLead());
        }
    }

    /**
     * Test that the tags will be added to the lead from the csv file.
     */
    public function testImportWithTagsInCsvFile(): void
    {
        $mockUserModel = $this->getMockBuilder(UserHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mockUserModel->method('getUser')
            ->willReturn(new User());

        $mockLeadModel = $this->getMockBuilder(LeadModelStub::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['saveEntity', 'checkForDuplicateContact', 'modifyTags'])
            ->getMock();

        $mockLeadModel->setUserHelper($mockUserModel);
        $this->setSecurity($mockLeadModel);

        $mockCompanyModel = $this->getMockBuilder(CompanyModel::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['extractCompanyDataFromImport'])
            ->getMock();

        $mockCompanyModel->expects($this->once())->method('extractCompanyDataFromImport')->willReturn([[], []]);

        $this->setProperty($mockLeadModel, LeadModel::class, 'companyModel', $mockCompanyModel);
        $this->setProperty($mockLeadModel, LeadModel::class, 'leadFields', [['alias' => 'email', 'type' => 'email', 'defaultValue' => '']]);

        $mockLeadModel->expects($this->once())->method('checkForDuplicateContact')->willReturn(new Lead());
        $mockLeadModel->expects($this->once())->method('modifyTags')->willReturn(true);

        $mockLeadModel->import(['tag' => 'tags'], ['tag' => 'Test 1|Test 2|Test 3']);
    }

    /**
     * Test lead matching by ID.
     */
    public function testImportMatchLeadById(): void
    {
        $leadEventLog  = new LeadEventLog();
        $lead          = new Lead();
        $lead->setId(21);

        $mockUserModel = $this->getMockBuilder(UserHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mockUserModel->method('getUser')
            ->willReturn(new User());

        $mockLeadModel = $this->getMockBuilder(LeadModelStub::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['saveEntity', 'getEntity'])
            ->getMock();

        $mockLeadModel->setUserHelper($mockUserModel);
        $this->setSecurity($mockLeadModel);

        $mockCompanyModel = $this->getMockBuilder(CompanyModel::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['extractCompanyDataFromImport'])
            ->getMock();

        $mockCompanyModel->expects($this->once())->method('extractCompanyDataFromImport')->willReturn([[], []]);

        $this->setProperty($mockLeadModel, LeadModel::class, 'companyModel', $mockCompanyModel);
        $this->setProperty($mockLeadModel, LeadModel::class, 'leadFields', [['alias' => 'email', 'type' => 'email', 'defaultValue' => '']]);

        $mockLeadModel->expects($this->once())->method('getEntity')->willReturn($lead);

        $merged = $mockLeadModel->import(['identifier' => 'id'], ['identifier' => '21'], null, null, null, true, $leadEventLog);
        $this->assertTrue($merged);
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
                [StagesChangeLog::class],
                [Stage::class]
            )
            ->willReturnOnConsecutiveCalls(
                $stagesChangeLogRepo,
                $stageRepositoryMock
            );

        $this->translator->expects($this->once())
            ->method('trans')
            ->with('mautic.stage.event.changed');

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
                [StagesChangeLog::class],
                [Stage::class]
            )
            ->willReturnOnConsecutiveCalls(
                $stagesChangeLogRepo,
                $stageRepositoryMock
            );

        $this->translator->expects($this->once())
            ->method('trans')
            ->with('mautic.lead.import.stage.not.exists', ['id' => $data['stage']])
            ->willReturn('Stage not found');

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

        $contact->expects($this->exactly(2))
            ->method('getUpdatedFields')
            ->willReturn([]);

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

    private function mockGetLeadRepository(): void
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

    public function testModifiedCompanies(): void
    {
        $lead          = $this->getLead(1);
        $companies     = [];
        $leadCompanies = [];

        for ($i = 1; $i <= 4; ++$i) {
            $companies[] = $i;
        }

        // Imitate that companies with id 3 and 4 are already added to the lead
        for ($i = 3; $i <= 4; ++$i) {
            // Taking only company_id into consideration as only this is required in this case
            $leadCompanies[] = ['company_id' => $i];
        }

        $this->companyModelMock->expects($this->once())
            ->method('getCompanyLeadRepository')
            ->willReturn($this->companyLeadRepositoryMock);

        $this->companyLeadRepositoryMock->expects($this->once())
            ->method('getCompaniesByLeadId')
            ->with($lead->getId())
            ->willReturn($leadCompanies);

        $this->companyModelMock->expects($this->once())
            ->method('addLeadToCompany')
            ->with([$companies[0], $companies[1]], $lead);

        $this->leadModel->modifyCompanies($lead, $companies);
    }

    private function getLead(int $id): Lead
    {
        return new class($id) extends Lead {
            public function __construct(
                private int $id,
            ) {
                parent::__construct();
            }

            public function getId(): int
            {
                return $this->id;
            }
        };
    }

    /**
     * @return Paginator<mixed[]>
     */
    private function getFieldPaginatorFake(): Paginator
    {
        return new class extends Paginator {
            public function __construct()
            {
            }

            /**
             * @return \ArrayIterator<int,array{label: string, alias: string, isPublished: bool, id: int, object: string, group: string, type: string}>
             */
            public function getIterator()
            {
                return new \ArrayIterator([
                    4 => ['label' => 'Email', 'alias' => 'email', 'isPublished' => true, 'id' => 4, 'object' => 'lead', 'group' => 'basic', 'type' => 'email'],
                    5 => ['label' => 'First Name', 'alias' => 'firstname', 'isPublished' => true, 'id' => 5, 'object' => 'lead', 'group' => 'basic', 'type' => 'text'],
                ]);
            }
        };
    }

    public function testDispatchBatchEvent(): void
    {
        $leadsParams = [];
        for ($x = 0; $x < 2; ++$x) {
            $lead = new Lead();
            $lead->setEmail(sprintf('test%s@test.cz', $x));
            $leadsParams[] = ['entity' => $lead, 'isNew'=> true, 'event'=> null];
        }
        $action = 'post_batch_save';

        // Lead Model that provides access to dispatchBatchEvent
        $leadModel = new class($this->requestStack, $this->ipLookupHelperMock, $this->pathsHelperMock, $this->integrationHelperkMock, $this->fieldModelMock, $this->fieldsWithUniqueIdentifier, $this->listModelMock, $this->formFactoryMock, $this->companyModelMock, $this->categoryModelMock, $this->channelListHelperMock, $this->coreParametersHelperMock, $this->emailValidatorMock, $this->userProviderMock, $this->contactTrackerMock, $this->deviceTrackerMock, $this->ipAddressModelMock, $this->entityManagerMock, $this->corePermissionsMock, $this->dispatcherMock, $this->urlGeneratorInterfaceMock, $this->translator, $this->userHelperMock, $this->logger) extends LeadModel {
            /**
             * @param array<mixed> $leads
             */
            public function dispatchBatchEventForTest(string $action, array $leads): ?\Symfony\Contracts\EventDispatcher\Event
            {
                return $this->dispatchBatchEvent($action, $leads);
            }
        };

        $leadEvent1 =  new LeadEvent($leadsParams[0]['entity'], $leadsParams[0]['isNew']);
        $leadEvent1->setEntityManager($this->entityManagerMock);

        $leadEvent2 =  new LeadEvent($leadsParams[1]['entity'], $leadsParams[1]['isNew']);
        $leadEvent2->setEntityManager($this->entityManagerMock);

        $event = new SaveBatchLeadsEvent([
            $leadEvent1,
            $leadEvent2,
        ]);

        $this->dispatcherMock->expects($this->once())
            ->method('hasListeners')
            ->with(LeadEvents::LEAD_POST_BATCH_SAVE)
            ->willReturn(true);
        $this->dispatcherMock->expects($this->once())
            ->method('dispatch')
            ->with($event, LeadEvents::LEAD_POST_BATCH_SAVE)
            ->willReturn($event);

        $leadModel->dispatchBatchEventForTest($action, $leadsParams);
    }

    private function setSecurity(LeadModel $companyModel): void
    {
        $security = $this->createMock(CorePermissions::class);
        $security->method('hasEntityAccess')
            ->willReturn(true);
        $security->method('isGranted')
            ->willReturn(true);

        $reflection = new \ReflectionClass($companyModel);
        $property   = $reflection->getProperty('security');
        $property->setAccessible(true);
        $property->setValue($companyModel, $security);
    }
}
