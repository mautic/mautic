<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Tests\Unit\Sync\DAO;

use Mautic\IntegrationsBundle\Sync\DAO\Sync\RelationsDAO;
use Mautic\IntegrationsBundle\Sync\DAO\Sync\Report\RelationDAO;
use PHPUnit\Framework\TestCase;

final class RelationsDAOTest extends TestCase
{
    public function testAddRelations(): void
    {
        $relationsDAO           = new RelationsDAO();
        $integrationObjectId    = 'IntegrationId-123';
        $integrationRelObjectId = 'IntegrationId-456';
        $objectName             = 'Contact';
        $relObjectName          = 'Account';
        $relationObject         = new RelationDAO(
            $objectName,
            $relObjectName,
            $relObjectName,
            $integrationObjectId,
            $integrationRelObjectId
        );

        $relations = ['AccountId' => $relationObject];

        $relationsDAO->addRelations($relations);

        $this->assertEquals($relationsDAO->current(), $relationObject);
        $this->assertSame($relationsDAO->current()->getObjectName(), $objectName);
        $this->assertSame($relationsDAO->current()->getRelObjectName(), $relObjectName);
        $this->assertSame($relationsDAO->current()->getObjectIntegrationId(), $integrationObjectId);
        $this->assertSame($relationsDAO->current()->getRelObjectIntegrationId(), $integrationRelObjectId);
    }
}
