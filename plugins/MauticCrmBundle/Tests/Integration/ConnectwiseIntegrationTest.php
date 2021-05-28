<?php

/*
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticCrmBundle\Tests\Integration;

use Mautic\PluginBundle\Model\IntegrationEntityModel;
use Mautic\PluginBundle\Tests\Integration\AbstractIntegrationTestCase;
use MauticPlugin\MauticCrmBundle\Api\ConnectwiseApi;
use MauticPlugin\MauticCrmBundle\Integration\ConnectwiseIntegration;

class ConnectwiseIntegrationTest extends AbstractIntegrationTestCase
{
    use DataGeneratorTrait;

    /**
     * @testdox Test that all records are fetched till last page of results are consumed
     * @covers  \MauticPlugin\MauticCrmBundle\Integration\ConnectwiseIntegration::getRecords()
     */
    public function testMultiplePagesOfRecordsAreFetched()
    {
        $this->reset();

        $apiHelper = $this->getMockBuilder(ConnectwiseApi::class)
            ->disableOriginalConstructor()
            ->getMock();

        $apiHelper->expects($this->exactly(2))
            ->method('getContacts')
            ->willReturnCallback(
                function () {
                    return $this->generateData(2);
                }
            );

        $integration = $this->getMockBuilder(ConnectwiseIntegration::class)
            ->disableOriginalConstructor()
            ->setMethodsExcept(['getRecords'])
            ->getMock();

        $integration->expects($this->once())
            ->method('isAuthorized')
            ->willReturn(true);

        $integration
            ->method('getApiHelper')
            ->willReturn($apiHelper);

        $integration->getRecords([], 'Contact');
    }

    /**
     * @testdox Test that all records are fetched till last page of results are consumed
     * @covers  \MauticPlugin\MauticCrmBundle\Integration\ConnectwiseIntegration::getCampaignMembers()
     */
    public function testMultiplePagesOfCampaignMemberRecordsAreFetched()
    {
        $this->reset();

        $apiHelper = $this->getMockBuilder(ConnectwiseApi::class)
            ->disableOriginalConstructor()
            ->getMock();

        $apiHelper->expects($this->exactly(2))
            ->method('getCampaignMembers')
            ->willReturnCallback(
                function () {
                    return $this->generateData(2);
                }
            );

        $integrationEntityModel = $this->getMockBuilder(IntegrationEntityModel::class)
            ->disableOriginalConstructor()
            ->getMock();

        $integration = $this->getMockBuilder(ConnectwiseIntegration::class)
            ->setConstructorArgs([
                $this->dispatcher,
                $this->cache,
                $this->em,
                $this->session,
                $this->request,
                $this->router,
                $this->translator,
                $this->logger,
                $this->encryptionHelper,
                $this->leadModel,
                $this->companyModel,
                $this->pathsHelper,
                $this->notificationModel,
                $this->fieldModel,
                $integrationEntityModel,
                $this->doNotContact,
            ])
            ->setMethodsExcept(['getCampaignMembers', 'getRecordList', 'setIntegrationEntityModel'])
            ->getMock();

        $integration->expects($this->once())
            ->method('isAuthorized')
            ->willReturn(true);

        $integration
            ->method('getApiHelper')
            ->willReturn($apiHelper);

        $integration->getCampaignMembers(1);
    }
}
