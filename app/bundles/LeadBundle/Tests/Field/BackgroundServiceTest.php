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

class BackgroundServiceTest extends \PHPUnit_Framework_TestCase
{
    public function testNoLeadField()
    {
        $fieldModel                         = $this->createMock(FieldModel::class);
        $customFieldColumn                  = $this->createMock(CustomFieldColumn::class);
        $leadFieldSaver                     = $this->createMock(LeadFieldSaver::class);
        $fieldColumnBackgroundJobDispatcher = $this->createMock(FieldColumnBackgroundJobDispatcher::class);
        $customFieldNotification            = $this->createMock(CustomFieldNotification::class);

        $backgroundService = new BackgroundService($fieldModel, $customFieldColumn, $leadFieldSaver, $fieldColumnBackgroundJobDispatcher, $customFieldNotification);

        $fieldModel->expects($this->once())
            ->method('getEntity')
            ->willReturn(null);

        $this->expectException(LeadFieldWasNotFoundException::class);
        $this->expectExceptionMessage('LeadField entity was not found');

        $backgroundService->addColumn(1, 3);
    }

    public function testColumnAlreadyCreated()
    {
        $fieldModel                         = $this->createMock(FieldModel::class);
        $customFieldColumn                  = $this->createMock(CustomFieldColumn::class);
        $leadFieldSaver                     = $this->createMock(LeadFieldSaver::class);
        $fieldColumnBackgroundJobDispatcher = $this->createMock(FieldColumnBackgroundJobDispatcher::class);
        $customFieldNotification            = $this->createMock(CustomFieldNotification::class);

        $backgroundService = new BackgroundService($fieldModel, $customFieldColumn, $leadFieldSaver, $fieldColumnBackgroundJobDispatcher, $customFieldNotification);

        $leadField = new LeadField();
        $leadField->setColumnWasCreated();

        $fieldModel->expects($this->once())
            ->method('getEntity')
            ->willReturn($leadField);

        $userId = 3;
        $customFieldNotification->expects($this->once())
            ->method('customFieldWasCreated')
            ->with($leadField, $userId);

        $this->expectException(ColumnAlreadyCreatedException::class);
        $this->expectExceptionMessage('Column was already created');

        $backgroundService->addColumn(1, $userId);
    }

    public function testAbortColumnCreate()
    {
        $fieldModel                         = $this->createMock(FieldModel::class);
        $customFieldColumn                  = $this->createMock(CustomFieldColumn::class);
        $leadFieldSaver                     = $this->createMock(LeadFieldSaver::class);
        $fieldColumnBackgroundJobDispatcher = $this->createMock(FieldColumnBackgroundJobDispatcher::class);
        $customFieldNotification            = $this->createMock(CustomFieldNotification::class);

        $backgroundService = new BackgroundService($fieldModel, $customFieldColumn, $leadFieldSaver, $fieldColumnBackgroundJobDispatcher, $customFieldNotification);

        $leadField = new LeadField();
        $leadField->setColumnIsNotCreated();

        $fieldModel->expects($this->once())
            ->method('getEntity')
            ->willReturn($leadField);

        $userId = 3;

        $fieldColumnBackgroundJobDispatcher->expects($this->once())
            ->method('dispatchPreAddColumnEvent')
            ->with($leadField)
            ->willThrowException(new AbortColumnCreateException('Message'));

        $this->expectException(AbortColumnCreateException::class);
        $this->expectExceptionMessage('Message');

        $backgroundService->addColumn(1, $userId);
    }

    public function testCustomFieldLimit()
    {
        $fieldModel                         = $this->createMock(FieldModel::class);
        $customFieldColumn                  = $this->createMock(CustomFieldColumn::class);
        $leadFieldSaver                     = $this->createMock(LeadFieldSaver::class);
        $fieldColumnBackgroundJobDispatcher = $this->createMock(FieldColumnBackgroundJobDispatcher::class);
        $customFieldNotification            = $this->createMock(CustomFieldNotification::class);

        $backgroundService = new BackgroundService($fieldModel, $customFieldColumn, $leadFieldSaver, $fieldColumnBackgroundJobDispatcher, $customFieldNotification);

        $leadField = new LeadField();
        $leadField->setColumnIsNotCreated();

        $fieldModel->expects($this->once())
            ->method('getEntity')
            ->willReturn($leadField);

        $fieldColumnBackgroundJobDispatcher->expects($this->once())
            ->method('dispatchPreAddColumnEvent')
            ->with($leadField);

        $customFieldColumn->expects($this->once())
            ->method('processCreateLeadColumn')
            ->with($leadField, false)
            ->willThrowException(new CustomFieldLimitException('Limit'));

        $userId = 3;
        $customFieldNotification->expects($this->once())
            ->method('customFieldLimitWasHit')
            ->with($leadField, $userId);

        $this->expectException(CustomFieldLimitException::class);
        $this->expectExceptionMessage('Limit');

        $backgroundService->addColumn(1, $userId);
    }

    public function testCreateColumnWithNoError()
    {
        $fieldModel                         = $this->createMock(FieldModel::class);
        $customFieldColumn                  = $this->createMock(CustomFieldColumn::class);
        $leadFieldSaver                     = $this->createMock(LeadFieldSaver::class);
        $fieldColumnBackgroundJobDispatcher = $this->createMock(FieldColumnBackgroundJobDispatcher::class);
        $customFieldNotification            = $this->createMock(CustomFieldNotification::class);

        $backgroundService = new BackgroundService($fieldModel, $customFieldColumn, $leadFieldSaver, $fieldColumnBackgroundJobDispatcher, $customFieldNotification);

        $leadField = new LeadField();
        $leadField->setColumnIsNotCreated();

        $fieldModel->expects($this->once())
            ->method('getEntity')
            ->willReturn($leadField);

        $fieldColumnBackgroundJobDispatcher->expects($this->once())
            ->method('dispatchPreAddColumnEvent')
            ->with($leadField);

        $customFieldColumn->expects($this->once())
            ->method('processCreateLeadColumn')
            ->with($leadField, false);

        $leadFieldSaver->expects($this->once())
            ->method('saveLeadFieldEntity')
            ->with($leadField, false);

        $userId = 3;
        $customFieldNotification->expects($this->once())
            ->method('customFieldWasCreated')
            ->with($leadField, $userId);

        $backgroundService->addColumn(1, $userId);

        $this->assertFalse($leadField->getColumnIsNotCreated());
    }
}
