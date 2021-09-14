<?php

declare(strict_types=1);

/*
 * @copyright   2019 Mautic, Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\IntegrationsBundle\Tests\Unit\EventListener;

use Mautic\IntegrationsBundle\Entity\FieldChangeRepository;
use Mautic\IntegrationsBundle\Entity\ObjectMappingRepository;
use Mautic\IntegrationsBundle\EventListener\LeadSubscriber;
use Mautic\IntegrationsBundle\Exception\IntegrationNotFoundException;
use Mautic\IntegrationsBundle\Helper\SyncIntegrationsHelper;
use Mautic\IntegrationsBundle\IntegrationEvents;
use Mautic\IntegrationsBundle\Sync\DAO\Value\EncodedValueDAO;
use Mautic\IntegrationsBundle\Sync\Exception\ObjectNotFoundException;
use Mautic\IntegrationsBundle\Sync\SyncDataExchange\Internal\Object\Contact;
use Mautic\IntegrationsBundle\Sync\SyncDataExchange\MauticSyncDataExchange;
use Mautic\IntegrationsBundle\Sync\VariableExpresser\VariableExpresserHelperInterface;
use Mautic\LeadBundle\Entity\Company;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Event\CompanyEvent;
use Mautic\LeadBundle\Event\LeadEvent;
use Mautic\LeadBundle\LeadEvents;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class LeadSubscriberTest extends TestCase
{
    /**
     * @var MockObject|FieldChangeRepository
     */
    private $fieldChangeRepository;

    /**
     * @var MockObject|ObjectMappingRepository
     */
    private $objectMappingRepository;

    /**
     * @var MockObject|VariableExpresserHelperInterface
     */
    private $variableExpresserHelper;

    /**
     * @var MockObject|SyncIntegrationsHelper
     */
    private $syncIntegrationsHelper;

    /**
     * @var MockObject|LeadEvent
     */
    private $leadEvent;

    /**
     * @var MockObject|CompanyEvent
     */
    private $companyEvent;

    /**
     * @var LeadSubscriber
     */
    private $subscriber;

    /**
     * @var MockObject|EventDispatcherInterface
     */
    private $eventDispatcherInterfaceMock;

    public function setUp(): void
    {
        parent::setUp();

        $this->fieldChangeRepository        = $this->createMock(FieldChangeRepository::class);
        $this->objectMappingRepository      = $this->createMock(ObjectMappingRepository::class);
        $this->variableExpresserHelper      = $this->createMock(VariableExpresserHelperInterface::class);
        $this->syncIntegrationsHelper       = $this->createMock(SyncIntegrationsHelper::class);
        $this->leadEvent                    = $this->createMock(LeadEvent::class);
        $this->companyEvent                 = $this->createMock(CompanyEvent::class);
        $this->eventDispatcherInterfaceMock = $this->createMock(EventDispatcherInterface::class);
        $this->subscriber                   = new LeadSubscriber(
            $this->fieldChangeRepository,
            $this->objectMappingRepository,
            $this->variableExpresserHelper,
            $this->syncIntegrationsHelper,
            $this->eventDispatcherInterfaceMock
        );
    }

    public function testGetSubscribedEvents(): void
    {
        $this->assertEquals(
            [
                LeadEvents::LEAD_POST_SAVE      => ['onLeadPostSave', 0],
                LeadEvents::LEAD_POST_DELETE    => ['onLeadPostDelete', 255],
                LeadEvents::COMPANY_POST_SAVE   => ['onCompanyPostSave', 0],
                LeadEvents::COMPANY_POST_DELETE => ['onCompanyPostDelete', 255],
                LeadEvents::LEAD_COMPANY_CHANGE => ['onLeadCompanyChange', 128],
            ],
            LeadSubscriber::getSubscribedEvents()
        );
    }

    public function testOnLeadPostSaveAnonymousLead(): void
    {
        $lead = $this->createMock(Lead::class);
        $lead->expects($this->once())
            ->method('isAnonymous')
            ->willReturn(true);
        $lead->expects($this->never())
            ->method('getChanges');

        $this->leadEvent->expects($this->once())
            ->method('getLead')
            ->willReturn($lead);

        $this->syncIntegrationsHelper->expects($this->never())
            ->method('hasObjectSyncEnabled');

        $this->subscriber->onLeadPostSave($this->leadEvent);
    }

    public function testOnLeadPostSaveLeadObjectSyncNotEnabled(): void
    {
        $lead = $this->createMock(Lead::class);
        $lead->expects($this->once())
            ->method('isAnonymous')
            ->willReturn(false);
        $lead->expects($this->never())
            ->method('getChanges');

        $this->leadEvent->expects($this->once())
            ->method('getLead')
            ->willReturn($lead);

        $this->syncIntegrationsHelper->expects($this->once())
            ->method('hasObjectSyncEnabled')
            ->with(Contact::NAME)
            ->willReturn(false);

        $this->subscriber->onLeadPostSave($this->leadEvent);
    }

    public function testOnLeadPostSaveNoAction(): void
    {
        $fieldChanges = [];

        $lead = $this->createMock(Lead::class);
        $lead->expects($this->once())
            ->method('isAnonymous')
            ->willReturn(false);
        $lead->expects($this->once())
            ->method('getChanges')
            ->willReturn($fieldChanges);

        $this->leadEvent->expects($this->once())
            ->method('getLead')
            ->willReturn($lead);

        $this->syncIntegrationsHelper->expects($this->once())
            ->method('hasObjectSyncEnabled')
            ->with(Contact::NAME)
            ->willReturn(true);

        $this->subscriber->onLeadPostSave($this->leadEvent);
    }

    /**
     * @throws IntegrationNotFoundException
     * @throws ObjectNotFoundException
     */
    public function testOnLeadPostSaveRecordChanges(): void
    {
        $fieldName    = 'fieldName';
        $oldValue     = 'oldValue';
        $newValue     = 'newValue';
        $fieldChanges = [
            'fields' => [
                $fieldName => [
                    $oldValue,
                    $newValue,
                ],
            ],
        ];
        $objectId   = 1;

        $lead = $this->createLeadMock($fieldChanges, $objectId);

        $this->leadEvent->expects($this->once())
            ->method('getLead')
            ->willReturn($lead);

        $this->syncIntegrationsHelper->expects($this->once())
            ->method('hasObjectSyncEnabled')
            ->with(Contact::NAME)
            ->willReturn(true);

        $this->handleRecordFieldChanges($fieldChanges['fields'], $objectId, Lead::class);

        $this->eventDispatcherInterfaceMock
            ->method('hasListeners')
            ->with(IntegrationEvents::INTEGRATION_BEFORE_CONTACT_FIELD_CHANGES)
            ->willReturn(true);

        $this->subscriber->onLeadPostSave($this->leadEvent);
    }

    /**
     * @throws IntegrationNotFoundException
     * @throws ObjectNotFoundException
     */
    public function testOnLeadPostSaveRecordChangesWithOwnerChange(): void
    {
        $newOwnerId   = 5;
        $fieldChanges = [
            'owner' => [
                2,
                $newOwnerId,
            ],
        ];
        $objectId   = 1;

        $lead = $this->createLeadMock($fieldChanges, $objectId);

        $this->leadEvent->expects($this->once())
            ->method('getLead')
            ->willReturn($lead);

        $this->syncIntegrationsHelper->expects($this->once())
            ->method('hasObjectSyncEnabled')
            ->with(Contact::NAME)
            ->willReturn(true);

        $fieldChanges['fields']['owner_id'] = $fieldChanges['owner'];

        $this->handleRecordFieldChanges($fieldChanges['fields'], $objectId, Lead::class);

        $this->eventDispatcherInterfaceMock
            ->method('hasListeners')
            ->with(IntegrationEvents::INTEGRATION_BEFORE_CONTACT_FIELD_CHANGES)
            ->willReturn(true);

        $this->subscriber->onLeadPostSave($this->leadEvent);
    }

    /**
     * @throws IntegrationNotFoundException
     * @throws ObjectNotFoundException
     */
    public function testOnLeadPostSaveRecordChangesWithPointChange(): void
    {
        $newPointCount   = 5;
        $fieldChanges    = [
            'points' => [
                2,
                $newPointCount,
            ],
        ];
        $objectId   = 1;

        $lead = $this->createLeadMock($fieldChanges, $objectId);

        $this->leadEvent->expects($this->once())
            ->method('getLead')
            ->willReturn($lead);

        $this->syncIntegrationsHelper->expects($this->once())
            ->method('hasObjectSyncEnabled')
            ->with(Contact::NAME)
            ->willReturn(true);

        $fieldChanges['fields']['points'] = $fieldChanges['points'];

        $this->handleRecordFieldChanges($fieldChanges['fields'], $objectId, Lead::class);

        $this->eventDispatcherInterfaceMock
            ->method('hasListeners')
            ->with(IntegrationEvents::INTEGRATION_BEFORE_CONTACT_FIELD_CHANGES)
            ->willReturn(true);

        $this->subscriber->onLeadPostSave($this->leadEvent);
    }

    public function testOnLeadPostDelete(): void
    {
        $deletedId       = '5';
        $lead            = new Lead();
        $lead->deletedId = $deletedId;

        $this->leadEvent->expects($this->exactly(2))
            ->method('getLead')
            ->willReturn($lead);

        $this->fieldChangeRepository->expects($this->once())
            ->method('deleteEntitiesForObject')
            ->with((int) $deletedId, MauticSyncDataExchange::OBJECT_CONTACT);

        $this->objectMappingRepository->expects($this->once())
            ->method('deleteEntitiesForObject')
            ->with((int) $deletedId, MauticSyncDataExchange::OBJECT_CONTACT);

        $this->subscriber->onLeadPostDelete($this->leadEvent);
    }

    public function testOnCompanyPostSaveSyncNotEnabled(): void
    {
        $this->syncIntegrationsHelper->expects($this->once())
            ->method('hasObjectSyncEnabled')
            ->with(MauticSyncDataExchange::OBJECT_COMPANY)
            ->willReturn(false);

        $this->companyEvent->expects($this->never())
            ->method('getCompany');

        $this->subscriber->onCompanyPostSave($this->companyEvent);
    }

    public function testOnCompanyPostSaveSyncNoAction(): void
    {
        $fieldChanges = [];

        $company = $this->createCompanyMock($fieldChanges, 1);

        $this->companyEvent->expects($this->once())
            ->method('getCompany')
            ->willReturn($company);

        $this->syncIntegrationsHelper->expects($this->once())
            ->method('hasObjectSyncEnabled')
            ->with(MauticSyncDataExchange::OBJECT_COMPANY)
            ->willReturn(true);

        $this->subscriber->onCompanyPostSave($this->companyEvent);
    }

    /**
     * @throws IntegrationNotFoundException
     * @throws ObjectNotFoundException
     */
    public function testOnCompanyPostSaveSyncRecordChanges(): void
    {
        $fieldName    = 'fieldName';
        $oldValue     = 'oldValue';
        $newValue     = 'newValue';
        $fieldChanges = [
            'fields' => [
                $fieldName => [
                    $oldValue,
                    $newValue,
                ],
            ],
        ];
        $objectId     = 1;

        $company = $this->createCompanyMock($fieldChanges, $objectId);

        $this->companyEvent->expects($this->once())
            ->method('getCompany')
            ->willReturn($company);

        $this->syncIntegrationsHelper->expects($this->once())
            ->method('hasObjectSyncEnabled')
            ->with(MauticSyncDataExchange::OBJECT_COMPANY)
            ->willReturn(true);

        $this->handleRecordFieldChanges($fieldChanges['fields'], $objectId, Company::class);

        $this->eventDispatcherInterfaceMock
            ->method('hasListeners')
            ->with(IntegrationEvents::INTEGRATION_BEFORE_COMPANY_FIELD_CHANGES)
            ->willReturn(true);

        $this->subscriber->onCompanyPostSave($this->companyEvent);
    }

    /**
     * @throws IntegrationNotFoundException
     * @throws ObjectNotFoundException
     */
    public function testOnCompanyPostSaveRecordChangesWithOwnerChange(): void
    {
        $newOwnerId   = 5;
        $fieldChanges = [
            'owner' => [
                2,
                $newOwnerId,
            ],
        ];
        $objectId     = 1;

        $company = $this->createCompanyMock($fieldChanges, $objectId);

        $this->companyEvent->expects($this->once())
            ->method('getCompany')
            ->willReturn($company);

        $this->syncIntegrationsHelper->expects($this->once())
            ->method('hasObjectSyncEnabled')
            ->with(MauticSyncDataExchange::OBJECT_COMPANY)
            ->willReturn(true);

        $fieldChanges['fields']['owner_id'] = $fieldChanges['owner'];

        $this->handleRecordFieldChanges($fieldChanges['fields'], $objectId, Company::class);

        $this->eventDispatcherInterfaceMock
            ->method('hasListeners')
            ->with(IntegrationEvents::INTEGRATION_BEFORE_COMPANY_FIELD_CHANGES)
            ->willReturn(true);

        $this->subscriber->onCompanyPostSave($this->companyEvent);
    }

    public function testOnCompanyPostDelete(): void
    {
        $deletedId       = '5';
        $lead            = new Company();
        $lead->deletedId = $deletedId;

        $this->companyEvent->expects($this->exactly(2))
            ->method('getCompany')
            ->willReturn($lead);

        $this->fieldChangeRepository->expects($this->once())
            ->method('deleteEntitiesForObject')
            ->with((int) $deletedId, MauticSyncDataExchange::OBJECT_COMPANY);

        $this->objectMappingRepository->expects($this->once())
            ->method('deleteEntitiesForObject')
            ->with((int) $deletedId, MauticSyncDataExchange::OBJECT_COMPANY);

        $this->subscriber->onCompanyPostDelete($this->companyEvent);
    }

    private function handleRecordFieldChanges(array $fieldChanges, int $objectId, string $objectType): void
    {
        $integrationName     = 'testIntegration';
        $enabledIntegrations = [$integrationName];

        $this->syncIntegrationsHelper->expects($this->any())
            ->method('getEnabledIntegrations')
            ->willReturn($enabledIntegrations);

        $fieldNames = [];
        $values     = [];
        $valueDAOs  = [];
        $i          = 0;

        foreach ($fieldChanges as $fieldName => [$oldValue, $newValue]) {
            $values[]     = [$newValue];
            $valueDAOs[]  = new EncodedValueDAO($objectType, (string) $newValue);
            $fieldNames[] = $fieldName;
        }

        $this->variableExpresserHelper->method('encodeVariable')
                ->withConsecutive(...$values)
                ->willReturn(...$valueDAOs);

        $this->fieldChangeRepository->expects($this->once())
            ->method('deleteEntitiesForObjectByColumnName')
            ->with($objectId, $objectType, $fieldNames);

        $this->fieldChangeRepository->expects($this->once())
            ->method('saveEntities');

        $this->fieldChangeRepository->expects($this->once())
            ->method('clear');
    }

    private function createLeadMock(array $fieldChanges, int $objectId): Lead
    {
        return new class($fieldChanges, $objectId) extends Lead {
            private $fieldChanges;
            private $objectId;

            public function __construct(array $fieldChanges, int $objectId)
            {
                parent::__construct();
                $this->fieldChanges = $fieldChanges;
                $this->objectId     = $objectId;
            }

            public function isAnonymous(): bool
            {
                return false;
            }

            public function getChanges($includePast = false): array
            {
                return $this->fieldChanges;
            }

            public function getId(): int
            {
                return $this->objectId;
            }
        };
    }

    private function createCompanyMock(array $fieldChanges, int $objectId): Company
    {
        return new class($fieldChanges, $objectId) extends Company {
            private $fieldChanges;
            private $objectId;

            public function __construct(array $fieldChanges, int $objectId)
            {
                $this->fieldChanges = $fieldChanges;
                $this->objectId     = $objectId;
            }

            public function getChanges($includePast = false): array
            {
                return $this->fieldChanges;
            }

            public function getId(): int
            {
                return $this->objectId;
            }
        };
    }
}
