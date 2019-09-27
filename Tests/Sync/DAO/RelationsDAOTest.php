<?php
/*
 * @copyright   2019 Mautic, Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\IntegrationsBundle\Tests\Sync\DAO;

use MauticPlugin\IntegrationsBundle\Sync\DAO\Sync\Report\RelationDAO;
use MauticPlugin\IntegrationsBundle\Sync\DAO\Sync\RelationsDAO;

class RelationsDAOTest extends \PHPUnit_Framework_TestCase
{
    public function testAddRelations()
    {
        $relationsDAO           = new RelationsDAO;
        $integrationObjectId    = 'IntegrationId-123';
        $integrationRelObjectId = 'IntegrationId-456';
        $objectName             = 'Contact';
        $relObjectName          = 'Account';

        $relationObject = new RelationDAO(
            $objectName,
            $relObjectName,
            $relObjectName,
            $integrationObjectId,
            $integrationRelObjectId
        );

        $relations = ['AccountId' => $relationObject];

        $relationsDAO->addRelations($relations);
        $returnedRelations = $relationsDAO->getRelations();

        $this->assertEquals($returnedRelations[0], $relationObject);
        $this->assertEquals($returnedRelations[0]->getObjectName(), $objectName);
        $this->assertEquals($returnedRelations[0]->getRelObjectName(), $relObjectName);
        $this->assertEquals($returnedRelations[0]->getObjectIntegrationId(), $integrationObjectId);
        $this->assertEquals($returnedRelations[0]->getRelObjectIntegrationId(), $integrationRelObjectId);
    }
}
