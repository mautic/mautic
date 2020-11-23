<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Tests\Field;

use Mautic\LeadBundle\Entity\LeadField;
use Mautic\LeadBundle\Field\BackgroundService;
use Mautic\LeadBundle\Field\CustomFieldColumn;
use Mautic\LeadBundle\Field\Dispatcher\FieldColumnBackgroundJobDispatcher;
use Mautic\LeadBundle\Field\Exception\AbortColumnCreateException;
use Mautic\LeadBundle\Field\Exception\ColumnAlreadyCreatedException;
use Mautic\LeadBundle\Field\Exception\CustomFieldLimitException;
use Mautic\LeadBundle\Field\Exception\LeadFieldWasNotFoundException;
use Mautic\LeadBundle\Field\LeadFieldSaver;
use Mautic\LeadBundle\Field\Notification\CustomFieldNotification;
use Mautic\LeadBundle\Model\FieldModel;

class BackgroundServiceTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|BackgroundService
     */
    private $backgroundService;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|FieldModel
     */
    private $fieldModel;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|CustomFieldColumn
     */
    private $customFieldColumn;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|LeadFieldSaver
     */
    private $leadFieldSaver;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|FieldColumnBackgroundJobDispatcher
     */
    private $fieldColumnBackgroundJobDispatcher;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|CustomFieldNotification
     */
    private $customFieldNotification;

    public function setUp(): void
    {
        $this->fieldModel                         = $this->createMock(FieldModel::class);
        $this->customFieldColumn                  = $this->createMock(CustomFieldColumn::class);
        $this->leadFieldSaver                     = $this->createMock(LeadFieldSaver::class);
        $this->fieldColumnBackgroundJobDispatcher = $this->createMock(FieldColumnBackgroundJobDispatcher::class);
        $this->customFieldNotification            = $this->createMock(CustomFieldNotification::class);

        $this->backgroundService = new BackgroundService(
            $this->fieldModel,
            $this->customFieldColumn,
            $this->leadFieldSaver,
            $this->fieldColumnBackgroundJobDispatcher,
            $this->customFieldNotification
        );
    }

    public function testNoLeadField()
    {
        $this->fieldModel->expects($this->once())
            ->method('getEntity')
            ->willReturn(null);

        $this->expectException(LeadFieldWasNotFoundException::class);
        $this->expectExceptionMessage('LeadField entity was not found');

        $this->backgroundService->addColumn(1, 3);
    }

    public function testColumnAlreadyCreated()
    {
        $leadField = new LeadField();
        $leadField->setColumnWasCreated();

        $this->fieldModel->expects($this->once())
            ->method('getEntity')
            ->willReturn($leadField);

        $userId = 3;
        $this->customFieldNotification->expects($this->once())
            ->method('customFieldWasCreated')
            ->with($leadField, $userId);

        $this->expectException(ColumnAlreadyCreatedException::class);
        $this->expectExceptionMessage('Column was already created');

        $this->backgroundService->addColumn(1, $userId);
    }

    public function testAbortColumnCreate()
    {
        $leadField = new LeadField();
        $leadField->setColumnIsNotCreated();

        $this->fieldModel->expects($this->once())
            ->method('getEntity')
            ->willReturn($leadField);

        $userId = 3;

        $this->fieldColumnBackgroundJobDispatcher->expects($this->once())
            ->method('dispatchPreAddColumnEvent')
            ->with($leadField)
            ->willThrowException(new AbortColumnCreateException('Message'));

        $this->expectException(AbortColumnCreateException::class);
        $this->expectExceptionMessage('Message');

        $this->backgroundService->addColumn(1, $userId);
    }

    public function testCustomFieldLimit()
    {
        $leadField = new LeadField();
        $leadField->setColumnIsNotCreated();

        $this->fieldModel->expects($this->once())
            ->method('getEntity')
            ->willReturn($leadField);

        $this->fieldColumnBackgroundJobDispatcher->expects($this->once())
            ->method('dispatchPreAddColumnEvent')
            ->with($leadField);

        $this->customFieldColumn->expects($this->once())
            ->method('processCreateLeadColumn')
            ->with($leadField, false)
            ->willThrowException(new CustomFieldLimitException('Limit'));

        $userId = 3;
        $this->customFieldNotification->expects($this->once())
            ->method('customFieldLimitWasHit')
            ->with($leadField, $userId);

        $this->expectException(CustomFieldLimitException::class);
        $this->expectExceptionMessage('Limit');

        $this->backgroundService->addColumn(1, $userId);
    }

    public function testCreateColumnWithNoError()
    {
        $leadField = new LeadField();
        $leadField->setColumnIsNotCreated();

        $this->fieldModel->expects($this->once())
            ->method('getEntity')
            ->willReturn($leadField);

        $this->fieldColumnBackgroundJobDispatcher->expects($this->once())
            ->method('dispatchPreAddColumnEvent')
            ->with($leadField);

        $this->customFieldColumn->expects($this->once())
            ->method('processCreateLeadColumn')
            ->with($leadField, false);

        $this->leadFieldSaver->expects($this->once())
            ->method('saveLeadFieldEntity')
            ->with($leadField, false);

        $userId = 3;
        $this->customFieldNotification->expects($this->once())
            ->method('customFieldWasCreated')
            ->with($leadField, $userId);

        $this->backgroundService->addColumn(1, $userId);

        $this->assertFalse($leadField->getColumnIsNotCreated());
    }
}
