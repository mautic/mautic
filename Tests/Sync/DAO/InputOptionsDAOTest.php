<?php

/*
 * @copyright   2019 Mautic Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://www.mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\IntegrationsBundle\Tests\Sync\DAO\Mapping;

use MauticPlugin\IntegrationsBundle\Sync\DAO\Sync\InputOptionsDAO;
use MauticPlugin\IntegrationsBundle\Exception\InvalidValueException;
use DateTimeImmutable;
use DateTimeZone;
use MauticPlugin\IntegrationsBundle\Sync\DAO\Sync\ObjectIdsDAO;
use MauticPlugin\IntegrationsBundle\Sync\SyncDataExchange\MauticSyncDataExchange;

class InputOptionsDAOTest extends \PHPUnit_Framework_TestCase
{
    public function testWorkflowFromCliWithAllValuesSet(): void
    {
        $objectIdsDAO = new InputOptionsDAO(
            [
                'integration'           => 'Magento',
                'first-time-sync'       => true,
                'disable-push'          => false,
                'disable-pull'          => true,
                'mautic-object-id'      => ['contact:12', 'contact:13', 'company:45'],
                'integration-object-id' => ['Lead:hfskjdhf', 'Lead:hfskjdhr'],
                'start-datetime'        => '2019-09-12T12:01:20',
                'end-datetime'          => '2019-10-12T12:01:20',
            ]
        );

        $this->assertSame('Magento', $objectIdsDAO->getIntegration());
        $this->assertTrue($objectIdsDAO->isFirstTimeSync());
        $this->assertFalse($objectIdsDAO->pullIsEnabled());
        $this->assertTrue($objectIdsDAO->pushIsEnabled());
        $this->assertSame(['12', '13'], $objectIdsDAO->getMauticObjectIds()->getObjectIdsFor(MauticSyncDataExchange::OBJECT_CONTACT));
        $this->assertSame(['45'], $objectIdsDAO->getMauticObjectIds()->getObjectIdsFor(MauticSyncDataExchange::OBJECT_COMPANY));
        $this->assertSame(['hfskjdhf', 'hfskjdhr'], $objectIdsDAO->getIntegrationObjectIds()->getObjectIdsFor('Lead'));
        $this->assertSame('2019-09-12T12:01:20+00:00', $objectIdsDAO->getStartDateTime()->format(DATE_ATOM));
        $this->assertSame('2019-10-12T12:01:20+00:00', $objectIdsDAO->getEndDateTime()->format(DATE_ATOM));
    }

    public function testWorkflowFromCliWithNoValuesSet(): void
    {
        $this->expectException(InvalidValueException::class);
        new InputOptionsDAO([]);
    }

    public function testWorkflowFromCliWithOnlyIntegrationValuesSet(): void
    {
        $objectIdsDAO = new InputOptionsDAO(['integration' => 'Magento']);
        $this->assertSame('Magento', $objectIdsDAO->getIntegration());
        $this->assertFalse($objectIdsDAO->isFirstTimeSync());
        $this->assertTrue($objectIdsDAO->pullIsEnabled());
        $this->assertTrue($objectIdsDAO->pushIsEnabled());
        $this->assertNull($objectIdsDAO->getMauticObjectIds());
        $this->assertNull($objectIdsDAO->getIntegrationObjectIds());
        $this->assertNull($objectIdsDAO->getStartDateTime());
        $this->assertNull($objectIdsDAO->getEndDateTime());
    }

    public function testWorkflowFromServiceWithAllValuesSet(): void
    {
        $mauticObjectIds      = new ObjectIdsDAO();
        $integrationObjectIds = new ObjectIdsDAO();
        $start                = new DateTimeImmutable('2019-09-12T12:01:20', new DateTimeZone('UTC'));
        $end                  = new DateTimeImmutable('2019-10-12T12:01:20', new DateTimeZone('UTC'));
        $objectIdsDAO         = new InputOptionsDAO(
            [
                'integration'           => 'Magento',
                'first-time-sync'       => true,
                'disable-push'          => false,
                'disable-pull'          => true,
                'mautic-object-id'      => $mauticObjectIds,
                'integration-object-id' => $integrationObjectIds,
                'start-datetime'        => $start,
                'end-datetime'          => $end,
            ]
        );

        $this->assertSame('Magento', $objectIdsDAO->getIntegration());
        $this->assertTrue($objectIdsDAO->isFirstTimeSync());
        $this->assertFalse($objectIdsDAO->pullIsEnabled());
        $this->assertTrue($objectIdsDAO->pushIsEnabled());
        $this->assertSame($mauticObjectIds, $objectIdsDAO->getMauticObjectIds());
        $this->assertSame($integrationObjectIds, $objectIdsDAO->getIntegrationObjectIds());
        $this->assertSame($start, $objectIdsDAO->getStartDateTime());
        $this->assertSame($end, $objectIdsDAO->getEndDateTime());
    }
}
