<?php

namespace Mautic\IntegrationsBundle\Tests\Unit\Sync\DAO;

use Mautic\IntegrationsBundle\Sync\DAO\Mapping\UpdatedObjectMappingDAO;
use PHPUnit\Framework\TestCase;

class UpdatedObjectMappingDAOTest extends TestCase
{
    public function testUpdatedObjectMappingDAO(): void
    {
        $integration           = 'integration';
        $integrationObjectName = 'contact';
        $integrationObjectId   = 1;
        $objectModifiedDate    = new \DateTime('2020-02-02');
        $internalObjectId      = 99;

        $updatedObjectMappingDAO = new UpdatedObjectMappingDAO(
            $integration,
            $integrationObjectName,
            $integrationObjectId,
            $objectModifiedDate,
            $internalObjectId
        );
        $this->assertEquals($integration, $updatedObjectMappingDAO->getIntegration());
        $this->assertEquals($integrationObjectName, $updatedObjectMappingDAO->getIntegrationObjectName());
        $this->assertEquals($integrationObjectId, $updatedObjectMappingDAO->getIntegrationObjectId());
        $this->assertEquals($objectModifiedDate, $updatedObjectMappingDAO->getObjectModifiedDate());
        $this->assertEquals($internalObjectId, $updatedObjectMappingDAO->getInternalObjectId());
    }
}
