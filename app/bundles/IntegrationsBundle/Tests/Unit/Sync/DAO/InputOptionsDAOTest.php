<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Tests\Unit\Sync\DAO;

use Mautic\IntegrationsBundle\Exception\InvalidValueException;
use Mautic\IntegrationsBundle\Sync\DAO\Sync\InputOptionsDAO;
use Mautic\IntegrationsBundle\Sync\DAO\Sync\ObjectIdsDAO;
use Mautic\IntegrationsBundle\Sync\SyncDataExchange\Internal\Object\Contact;
use Mautic\IntegrationsBundle\Sync\SyncDataExchange\MauticSyncDataExchange;
use PHPUnit\Framework\TestCase;

class InputOptionsDAOTest extends TestCase
{
    public function testWorkflowFromCliWithAllValuesSet(): void
    {
        $inputOptionsDAO = new InputOptionsDAO(
            [
                'integration'           => 'Magento',
                'first-time-sync'       => true,
                'disable-push'          => false,
                'disable-pull'          => true,
                'disable-activity-push' => true,
                'mautic-object-id'      => ['contact:12', 'contact:13', 'company:45'],
                'integration-object-id' => ['Lead:hfskjdhf', 'Lead:hfskjdhr'],
                'start-datetime'        => '2019-09-12T12:01:20',
                'end-datetime'          => '2019-10-12T12:01:20',
                'option'                => ['custom1:1', 'custom2:2'],
            ]
        );

        $this->assertSame('Magento', $inputOptionsDAO->getIntegration());
        $this->assertTrue($inputOptionsDAO->isFirstTimeSync());
        $this->assertFalse($inputOptionsDAO->pullIsEnabled());
        $this->assertTrue($inputOptionsDAO->pushIsEnabled());
        $this->assertFalse($inputOptionsDAO->activityPushIsEnabled());
        $this->assertSame(['12', '13'], $inputOptionsDAO->getMauticObjectIds()->getObjectIdsFor(Contact::NAME));
        $this->assertSame(['45'], $inputOptionsDAO->getMauticObjectIds()->getObjectIdsFor(MauticSyncDataExchange::OBJECT_COMPANY));
        $this->assertSame(['hfskjdhf', 'hfskjdhr'], $inputOptionsDAO->getIntegrationObjectIds()->getObjectIdsFor('Lead'));
        $this->assertSame('2019-09-12T12:01:20+00:00', $inputOptionsDAO->getStartDateTime()->format(DATE_ATOM));
        $this->assertSame('2019-10-12T12:01:20+00:00', $inputOptionsDAO->getEndDateTime()->format(DATE_ATOM));
        $this->assertSame(['custom1' => '1', 'custom2' => '2'], $inputOptionsDAO->getOptions());
    }

    public function testWorkflowFromCliWithNoValuesSet(): void
    {
        $this->expectException(InvalidValueException::class);
        new InputOptionsDAO([]);
    }

    public function testWorkflowFromCliWithOnlyIntegrationValuesSet(): void
    {
        $inputOptionsDAO = new InputOptionsDAO(['integration' => 'Magento']);
        $this->assertSame('Magento', $inputOptionsDAO->getIntegration());
        $this->assertFalse($inputOptionsDAO->isFirstTimeSync());
        $this->assertTrue($inputOptionsDAO->pullIsEnabled());
        $this->assertTrue($inputOptionsDAO->pushIsEnabled());
        $this->assertTrue($inputOptionsDAO->activityPushIsEnabled());
        $this->assertNull($inputOptionsDAO->getMauticObjectIds());
        $this->assertNull($inputOptionsDAO->getIntegrationObjectIds());
        $this->assertNull($inputOptionsDAO->getStartDateTime());
        $this->assertNull($inputOptionsDAO->getEndDateTime());
        $this->assertEmpty($inputOptionsDAO->getOptions());
    }

    public function testWorkflowFromServiceWithAllValuesSet(): void
    {
        $mauticObjectIds      = new ObjectIdsDAO();
        $integrationObjectIds = new ObjectIdsDAO();
        $start                = new \DateTimeImmutable('2019-09-12T12:01:20', new \DateTimeZone('UTC'));
        $end                  = new \DateTimeImmutable('2019-10-12T12:01:20', new \DateTimeZone('UTC'));
        $options              = ['custom1' => 1, 'custom2' => 2];
        $inputOptionsDAO      = new InputOptionsDAO(
            [
                'integration'           => 'Magento',
                'first-time-sync'       => true,
                'disable-push'          => false,
                'disable-pull'          => true,
                'disable-activity-push' => false,
                'mautic-object-id'      => $mauticObjectIds,
                'integration-object-id' => $integrationObjectIds,
                'start-datetime'        => $start,
                'end-datetime'          => $end,
                'options'               => $options,
            ]
        );

        $this->assertSame('Magento', $inputOptionsDAO->getIntegration());
        $this->assertTrue($inputOptionsDAO->isFirstTimeSync());
        $this->assertFalse($inputOptionsDAO->pullIsEnabled());
        $this->assertTrue($inputOptionsDAO->pushIsEnabled());
        $this->assertTrue($inputOptionsDAO->activityPushIsEnabled());
        $this->assertSame($mauticObjectIds, $inputOptionsDAO->getMauticObjectIds());
        $this->assertSame($integrationObjectIds, $inputOptionsDAO->getIntegrationObjectIds());
        $this->assertSame($start, $inputOptionsDAO->getStartDateTime());
        $this->assertSame($end, $inputOptionsDAO->getEndDateTime());
        $this->assertSame($options, $inputOptionsDAO->getOptions());
    }
}
