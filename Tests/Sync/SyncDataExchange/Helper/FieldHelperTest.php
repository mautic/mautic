<?php

/*
 * @copyright   2018 Mautic Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://www.mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\IntegrationsBundle\Sync\SyncDataExchange\Helper;


use Mautic\ChannelBundle\Helper\ChannelListHelper;
use Mautic\LeadBundle\Model\FieldModel;
use MauticPlugin\IntegrationsBundle\Sync\SyncDataExchange\MauticSyncDataExchange;
use MauticPlugin\IntegrationsBundle\Sync\VariableExpresser\VariableExpresserHelperInterface;
use Symfony\Component\Translation\TranslatorInterface;

class FieldHelperTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var FieldModel|\PHPUnit_Framework_MockObject_MockObject
     */
    private $fieldModel;

    /**
     * @var VariableExpresserHelperInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $variableExpresserHelper;

    /**
     * @var ChannelListHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    private $channelListHelper;


    protected function setUp()
    {
        $this->fieldModel = $this->createMock(FieldModel::class);
        $this->fieldModel->method('getFieldList')
            ->willReturn([ 'email' => 'Email']);
        $this->variableExpresserHelper = $this->createMock(VariableExpresserHelperInterface::class);
        $this->channelListHelper = $this->createMock(ChannelListHelper::class);
        $this->channelListHelper->method('getFeatureChannels')
            ->willReturn(['Email' => 'email']);
    }

    public function testContactSyncFieldsReturned()
    {
        $fields = $this->getFieldHelper()->getSyncFields(MauticSyncDataExchange::OBJECT_CONTACT);

        $this->assertEquals(['mautic_internal_dnc_email', 'mautic_internal_id', 'mautic_internal_contact_timeline', 'email'], array_keys($fields));
    }

    public function testCompanySyncFieldsReturned()
    {
        $fields = $this->getFieldHelper()->getSyncFields(MauticSyncDataExchange::OBJECT_COMPANY);

        $this->assertEquals(['mautic_internal_id', 'email'], array_keys($fields));
    }

    private function getFieldHelper()
    {
        return new FieldHelper($this->fieldModel, $this->variableExpresserHelper, $this->channelListHelper, $this->createMock(TranslatorInterface::class));
    }
}