<?php

/*
 * @copyright   2019 Mautic, Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\IntegrationsBundle\Tests\Unit\EventListener;

use Mautic\LeadBundle\Entity\Company;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Event\CompanyEvent;
use Mautic\LeadBundle\Event\LeadEvent;
use Mautic\LeadBundle\LeadEvents;
use MauticPlugin\IntegrationsBundle\Entity\FieldChangeRepository;
use MauticPlugin\IntegrationsBundle\EventListener\LeadSubscriber;
use MauticPlugin\IntegrationsBundle\Helper\SyncIntegrationsHelper;
use MauticPlugin\IntegrationsBundle\Sync\DAO\Value\EncodedValueDAO;
use MauticPlugin\IntegrationsBundle\Sync\SyncDataExchange\MauticSyncDataExchange;
use MauticPlugin\IntegrationsBundle\Sync\VariableExpresser\VariableExpresserHelperInterface;

class LeadSubscriberTest extends \PHPUnit_Framework_TestCase
{
    private $fieldChangeRepository;
    private $variableExpresserHelper;
    private $syncIntegrationsHelper;
    private $subscriber;

    public function setUp()
    {
        parent::setUp();

        $this->fieldChangeRepository = $this->createMock(FieldChangeRepository::class);
        $this->variableExpresserHelper = $this->createMock(VariableExpresserHelperInterface::class);
        $this->syncIntegrationsHelper = $this->createMock(SyncIntegrationsHelper::class);
        $this->subscriber = new LeadSubscriber(
            $this->fieldChangeRepository,
            $this->variableExpresserHelper,
            $this->syncIntegrationsHelper
        );
    }

    public function testGetSubscribedEvents()
    {
        $this->assertEquals(
            [
                LeadEvents::LEAD_POST_SAVE      => ['onLeadPostSave', 0],
                LeadEvents::LEAD_POST_DELETE    => ['onLeadPostDelete', 255],
                LeadEvents::COMPANY_POST_SAVE   => ['onCompanyPostSave', 0],
                LeadEvents::COMPANY_POST_DELETE => ['onCompanyPostDelete', 255],
            ],
            LeadSubscriber::getSubscribedEvents()
        );
    }

    public function testOnLeadPostSaveAnonymousLead()
    {
        $lead = $this->createMock(Lead::class);
        $lead->expects($this->at(0))
            ->method('isAnonymous')
            ->willReturn(true);
        $lead->expects($this->never())
            ->method('getChanges');

        $event = $this->createMock(LeadEvent::class);
        $event->expects($this->once())
            ->method('getLead')
            ->willReturn($lead);

        $this->syncIntegrationsHelper->expects($this->never())
            ->method('hasObjectSyncEnabled');

        $this->subscriber->onLeadPostSave($event);
    }

    public function testOnLeadPostSaveLeadObjectSyncNotEnabled()
    {
        $lead = $this->createMock(Lead::class);
        $lead->expects($this->at(0))
            ->method('isAnonymous')
            ->willReturn(false);
        $lead->expects($this->never())
            ->method('getChanges');

        $event = $this->createMock(LeadEvent::class);
        $event->expects($this->once())
            ->method('getLead')
            ->willReturn($lead);

        $this->syncIntegrationsHelper->expects($this->once())
            ->method('hasObjectSyncEnabled')
            ->with(MauticSyncDataExchange::OBJECT_CONTACT)
            ->willReturn(false);

        $this->subscriber->onLeadPostSave($event);
    }

    public function testOnLeadPostSaveNoAction()
    {
        $fieldChanges = [];

        $lead = $this->createMock(Lead::class);
        $lead->expects($this->at(0))
            ->method('isAnonymous')
            ->willReturn(false);
        $lead->expects($this->once())
            ->method('getChanges')
            ->willReturn($fieldChanges);

        $event = $this->createMock(LeadEvent::class);
        $event->expects($this->once())
            ->method('getLead')
            ->willReturn($lead);

        $this->syncIntegrationsHelper->expects($this->once())
            ->method('hasObjectSyncEnabled')
            ->with(MauticSyncDataExchange::OBJECT_CONTACT)
            ->willReturn(true);

        $this->subscriber->onLeadPostSave($event);
    }

    public function testOnLeadPostSaveRecordChanges()
    {
        $fieldName = 'fieldName';
        $oldValue = 'oldValue';
        $newValue = 'newValue';
        $fieldChanges = [
            'fields' => [
                $fieldName => [
                    $oldValue,
                    $newValue,
                ],
            ],
        ];
        $objectId = 1;
        $objectType = Lead::class;

        $lead = $this->createMock(Lead::class);
        $lead->expects($this->at(0))
            ->method('isAnonymous')
            ->willReturn(false);
        $lead->expects($this->once())
            ->method('getChanges')
            ->willReturn($fieldChanges);
        $lead->expects($this->once())
            ->method('getId')
            ->willReturn($objectId);

        $event = $this->createMock(LeadEvent::class);
        $event->expects($this->once())
            ->method('getLead')
            ->willReturn($lead);

        $this->syncIntegrationsHelper->expects($this->once())
            ->method('hasObjectSyncEnabled')
            ->with(MauticSyncDataExchange::OBJECT_CONTACT)
            ->willReturn(true);

        $this->handleRecordFieldChanges($fieldChanges['fields'], $objectId, $objectType);

        $this->subscriber->onLeadPostSave($event);
    }

    public function testOnLeadPostSaveRecordChangesWithOwnerChange()
    {
        $newOwnerId = 5;
        $fieldChanges = [
            'owner' => [
                2,
                $newOwnerId,
            ]
        ];
        $objectId = 1;
        $objectType = Lead::class;

        $lead = $this->createMock(Lead::class);
        $lead->expects($this->at(0))
            ->method('isAnonymous')
            ->willReturn(false);
        $lead->expects($this->once())
            ->method('getChanges')
            ->willReturn($fieldChanges);
        $lead->expects($this->once())
            ->method('getId')
            ->willReturn($objectId);

        $event = $this->createMock(LeadEvent::class);
        $event->expects($this->once())
            ->method('getLead')
            ->willReturn($lead);

        $this->syncIntegrationsHelper->expects($this->once())
            ->method('hasObjectSyncEnabled')
            ->with(MauticSyncDataExchange::OBJECT_CONTACT)
            ->willReturn(true);

        $fieldChanges['fields']['owner_id'] = $fieldChanges['owner'];

        $this->handleRecordFieldChanges($fieldChanges['fields'], $objectId, $objectType);

        $this->subscriber->onLeadPostSave($event);
    }

    public function testOnLeadPostDelete()
    {
        $deletedId = '5';

        $lead = new Lead();
        $lead->deletedId = $deletedId;

        $event = $this->createMock(LeadEvent::class);
        $event->expects($this->once())
            ->method('getLead')
            ->willReturn($lead);

        $this->fieldChangeRepository->expects($this->once())
            ->method('deleteEntitiesForObject')
            ->with((int) $deletedId, Lead::class);

        $this->subscriber->onLeadPostDelete($event);
    }

    public function testOnCompanyPostSaveSyncNotEnabled()
    {
        $event = $this->createMock(CompanyEvent::class);

        $this->syncIntegrationsHelper->expects($this->once())
            ->method('hasObjectSyncEnabled')
            ->with(MauticSyncDataExchange::OBJECT_COMPANY)
            ->willReturn(false);

        $event->expects($this->never())
            ->method('getCompany');

        $this->subscriber->onCompanyPostSave($event);
    }

    public function testOnCompanyPostSaveSyncNoAction()
    {
        $fieldChanges = [];

        $company = $this->createMock(Company::class);
        $company->expects($this->once())
            ->method('getChanges')
            ->willReturn($fieldChanges);

        $event = $this->createMock(CompanyEvent::class);
        $event->expects($this->once())
            ->method('getCompany')
            ->willReturn($company);

        $this->syncIntegrationsHelper->expects($this->once())
            ->method('hasObjectSyncEnabled')
            ->with(MauticSyncDataExchange::OBJECT_COMPANY)
            ->willReturn(true);

        $this->subscriber->onCompanyPostSave($event);
    }

    public function testOnCompanyPostSaveSyncRecordChanges()
    {
        $fieldName = 'fieldName';
        $oldValue = 'oldValue';
        $newValue = 'newValue';
        $fieldChanges = [
            'fields' => [
                $fieldName => [
                    $oldValue,
                    $newValue,
                ],
            ],
        ];
        $objectId = 1;
        $objectType = Company::class;

        $company = $this->createMock(Company::class);
        $company->expects($this->once())
            ->method('getChanges')
            ->willReturn($fieldChanges);
        $company->expects($this->once())
            ->method('getChanges')
            ->willReturn($fieldChanges);
        $company->expects($this->once())
            ->method('getId')
            ->willReturn($objectId);


        $event = $this->createMock(CompanyEvent::class);
        $event->expects($this->once())
            ->method('getCompany')
            ->willReturn($company);

        $this->syncIntegrationsHelper->expects($this->once())
            ->method('hasObjectSyncEnabled')
            ->with(MauticSyncDataExchange::OBJECT_COMPANY)
            ->willReturn(true);

        $this->handleRecordFieldChanges($fieldChanges['fields'], $objectId, $objectType);

        $this->subscriber->onCompanyPostSave($event);
    }

    public function testOnCompanyPostSaveRecordChangesWithOwnerChange()
    {
        $newOwnerId = 5;
        $fieldChanges = [
            'owner' => [
                2,
                $newOwnerId,
            ]
        ];
        $objectId = 1;
        $objectType = Company::class;

        $company = $this->createMock(Company::class);
        $company->expects($this->once())
            ->method('getChanges')
            ->willReturn($fieldChanges);
        $company->expects($this->once())
            ->method('getId')
            ->willReturn($objectId);

        $event = $this->createMock(CompanyEvent::class);
        $event->expects($this->once())
            ->method('getCompany')
            ->willReturn($company);

        $this->syncIntegrationsHelper->expects($this->once())
            ->method('hasObjectSyncEnabled')
            ->with(MauticSyncDataExchange::OBJECT_COMPANY)
            ->willReturn(true);

        $fieldChanges['fields']['owner_id'] = $fieldChanges['owner'];

        $this->handleRecordFieldChanges($fieldChanges['fields'], $objectId, $objectType);

        $this->subscriber->onCompanyPostSave($event);
    }

    public function testOnCompanyPostDelete()
    {
        $deletedId = '5';

        $lead = new Company();
        $lead->deletedId = $deletedId;

        $event = $this->createMock(CompanyEvent::class);
        $event->expects($this->once())
            ->method('getCompany')
            ->willReturn($lead);

        $this->fieldChangeRepository->expects($this->once())
            ->method('deleteEntitiesForObject')
            ->with((int) $deletedId, Company::class);

        $this->subscriber->onCompanyPostDelete($event);
    }

    private function handleRecordFieldChanges(array $fieldChanges, int $objectId, string $objectType): void
    {
        $integrationName = 'testIntegration';
        $enabledIntegrations = [$integrationName];

        $this->syncIntegrationsHelper->expects($this->any())
            ->method('getEnabledIntegrations')
            ->willReturn($enabledIntegrations);

        $fieldNames = [];
        $i = 0;
        foreach ($fieldChanges as $fieldName => list($oldValue, $newValue)) {
            $valueDao = new EncodedValueDAO($objectType, (string) $newValue);

            $this->variableExpresserHelper->expects($this->at($i))
                ->method('encodeVariable')
                ->with($newValue)
                ->willReturn($valueDao);

            $fieldNames[] = $fieldName;
        }

        $this->fieldChangeRepository->expects($this->once())
            ->method('deleteEntitiesForObjectByColumnName')
            ->with($objectId, $objectType, $fieldNames);

        $this->fieldChangeRepository->expects($this->once())
            ->method('saveEntities');

        $this->fieldChangeRepository->expects($this->once())
            ->method('clear');
    }
}
