<?php

declare(strict_types=1);

/*
 * @copyright   2018 Mautic Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://www.mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\IntegrationsBundle\Tests\Unit\Sync\SyncDataExchange\Helper;

use Mautic\ChannelBundle\Helper\ChannelListHelper;
use Mautic\LeadBundle\Model\FieldModel;
use MauticPlugin\IntegrationsBundle\Event\MauticSyncFieldsLoadEvent;
use MauticPlugin\IntegrationsBundle\Sync\SyncDataExchange\Helper\FieldHelper;
use MauticPlugin\IntegrationsBundle\Sync\SyncDataExchange\Internal\Object\Contact;
use MauticPlugin\IntegrationsBundle\Sync\SyncDataExchange\Internal\ObjectProvider;
use MauticPlugin\IntegrationsBundle\Sync\VariableExpresser\VariableExpresserHelperInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
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

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $eventDispatcher;

    /**
     * @var MauticSyncFieldsLoadEvent|\PHPUnit_Framework_MockObject_MockObject
     */
    private $mauticSyncFieldsLoadEvent;

    /**
     * @var ObjectProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    private $objectProvider;

    /**
     * @var FieldHelper
     */
    private $fieldHelper;

    protected function setUp(): void
    {
        $this->fieldModel              = $this->createMock(FieldModel::class);
        $this->variableExpresserHelper = $this->createMock(VariableExpresserHelperInterface::class);
        $this->channelListHelper       = $this->createMock(ChannelListHelper::class);
        $this->objectProvider          = $this->createMock(ObjectProvider::class);
        $this->channelListHelper->method('getFeatureChannels')
            ->willReturn(['Email' => 'email']);

        $this->mauticSyncFieldsLoadEvent = $this->createMock(MauticSyncFieldsLoadEvent::class);
        $this->eventDispatcher           = $this->createMock(EventDispatcherInterface::class);
        $this->eventDispatcher->method('dispatch')
            ->willReturn($this->mauticSyncFieldsLoadEvent);

        $this->fieldHelper = new FieldHelper(
            $this->fieldModel,
            $this->variableExpresserHelper,
            $this->channelListHelper,
            $this->createMock(TranslatorInterface::class),
            $this->eventDispatcher,
            $this->objectProvider
        );
    }

    public function testContactSyncFieldsReturned(): void
    {
        $objectName = Contact::NAME;
        $syncFields = ['email' => 'Email'];

        $this->mauticSyncFieldsLoadEvent->method('getObjectName')
            ->willReturn($objectName);
        $this->mauticSyncFieldsLoadEvent->method('getFields')
            ->willReturn($syncFields);

        $this->fieldModel->method('getFieldList')
            ->willReturn($syncFields);

        $fields = $this->fieldHelper->getSyncFields($objectName);

        $this->assertEquals(
            [
                'mautic_internal_dnc_email',
                'mautic_internal_id',
                'mautic_internal_contact_timeline',
                'email',
            ],
            array_keys($fields)
        );
    }

    public function testCompanySyncFieldsReturned(): void
    {
        $objectName = Contact::NAME;
        $syncFields = ['email' => 'Email'];

        $this->mauticSyncFieldsLoadEvent->method('getObjectName')
            ->willReturn($objectName);
        $this->mauticSyncFieldsLoadEvent->method('getFields')
            ->willReturn($syncFields);

        $this->fieldModel->method('getFieldList')
            ->willReturn($syncFields);

        $fields = $this->fieldHelper->getSyncFields($objectName);

        $this->assertEquals(
            [
                'mautic_internal_dnc_email',
                'mautic_internal_id',
                'mautic_internal_contact_timeline',
                'email',
            ],
            array_keys($fields)
        );
    }

    public function testGetRequiredFieldsForContact(): void
    {
        $this->fieldModel->expects($this->once())
            ->method('getFieldList')
            ->willReturn(['some fields']);

        $this->fieldModel->expects($this->once())
            ->method('getUniqueIdentifierFields')
            ->willReturn(['some unique fields']);

        $this->assertSame(
            ['some fields', 'some unique fields'],
            $this->fieldHelper->getRequiredFields('lead')
        );
    }

    public function testGetRequiredFieldsForCompany(): void
    {
        $this->fieldModel->expects($this->once())
            ->method('getFieldList')
            ->willReturn(['some fields']);

        $this->fieldModel->expects($this->never())
            ->method('getUniqueIdentifierFields');

        $this->assertSame(
            ['some fields'],
            $this->fieldHelper->getRequiredFields('company')
        );
    }

    public function testGetFieldObjectName(): void
    {
        $this->objectProvider->expects($this->once())
            ->method('getObjectByName')
            ->with(Contact::NAME)
            ->willReturn(new Contact());

        $this->assertSame(
            Contact::ENTITY,
            $this->fieldHelper->getFieldObjectName(Contact::NAME)
        );
    }
}
