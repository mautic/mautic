<?php

/*
 * @copyright   2018 Mautic Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://www.mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\IntegrationsBundle\Tests\Sync\DAO\Mapping;


use MauticPlugin\IntegrationsBundle\Sync\DAO\Mapping\MappingManualDAO;
use MauticPlugin\IntegrationsBundle\Sync\DAO\Mapping\ObjectMappingDAO;
use MauticPlugin\IntegrationsBundle\Sync\SyncDataExchange\MauticSyncDataExchange;

class MappingManualDAOTest extends \PHPUnit_Framework_TestCase
{
    private $integrationName = 'Test';
    private $integrationObjectName = 'Contact';

    public function testMappedIntegrationNamesAreReturnedBasedOnInternalObjectName()
    {
        $this->assertEquals(
            [$this->integrationObjectName],
            $this->getMappingManualDAO()->getIntegrationObjectNames(MauticSyncDataExchange::OBJECT_CONTACT)
        );
    }

    public function testMappedInternalNamesAreReturnedBasedOnIntegrationObjectName()
    {
        $this->assertEquals(
            [MauticSyncDataExchange::OBJECT_CONTACT],
            $this->getMappingManualDAO()->getInternalObjectNames($this->integrationObjectName)
        );
    }

    public function testThatOneWayInternalObjectFieldsAreNotReturnedWhenNotRequired()
    {
        $this->assertEquals(
            [
                'email',    // required and bidirectional
                'country',  // bidirectional
                'firstname' // sync from mautic to integration
            ],
            $this->getMappingManualDAO()->getInternalObjectFieldsToSyncToIntegration(MauticSyncDataExchange::OBJECT_CONTACT)
        );
    }

    public function testThatRequiredInternalObjectFieldsAreReturned()
    {
        $this->assertEquals(
            ['email'],
            $this->getMappingManualDAO()->getInternalObjectRequiredFieldNames(MauticSyncDataExchange::OBJECT_CONTACT)
        );
    }

    public function testThatOneWayIntegrationObjectFieldsAreNotReturnedWhenNotRequired()
    {
        $this->assertEquals(
            [
                'email',    // required and bidirectional
                'country',  // bidirectional
                'last_name' // sync from mautic to integration
            ],
            $this->getMappingManualDAO()->getIntegrationObjectFieldsToSyncToMautic($this->integrationObjectName)
        );
    }

    public function testThatRequiredIntegrationObjectFieldsAreReturned()
    {
        $this->assertEquals(
            ['email'],
            $this->getMappingManualDAO()->getIntegrationObjectRequiredFieldNames($this->integrationObjectName)
        );
    }

    public function testMappedIntegrationFieldIsReturned()
    {
        $this->assertEquals(
            'last_name',
            $this->getMappingManualDAO()->getIntegrationMappedField(
                $this->integrationObjectName,
                MauticSyncDataExchange::OBJECT_CONTACT,
                'lastname'
            )
        );
    }

    public function testMappedInternalFieldIsReturned()
    {
        $this->assertEquals(
            'lastname',
            $this->getMappingManualDAO()->getInternalMappedField(
                MauticSyncDataExchange::OBJECT_CONTACT,
                $this->integrationObjectName,
                'last_name'
            )
        );
    }

    private function getMappingManualDAO()
    {
        $mappingManual = new MappingManualDAO($this->integrationName);
        $objectMapping = new ObjectMappingDAO(MauticSyncDataExchange::OBJECT_CONTACT, $this->integrationObjectName);
        $objectMapping->addFieldMapping('email', 'email', ObjectMappingDAO::SYNC_BIDIRECTIONALLY, true);
        $objectMapping->addFieldMapping('country', 'country', ObjectMappingDAO::SYNC_BIDIRECTIONALLY);
        $objectMapping->addFieldMapping('firstname', 'first_name', ObjectMappingDAO::SYNC_TO_INTEGRATION);
        $objectMapping->addFieldMapping('lastname', 'last_name', ObjectMappingDAO::SYNC_TO_MAUTIC);

        $mappingManual->addObjectMapping($objectMapping);

        return $mappingManual;
    }
}