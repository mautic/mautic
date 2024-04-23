<?php

declare(strict_types=1);

namespace Mautic\FormBundle\Tests\EventListener;

use Mautic\FormBundle\Entity\Field;
use Mautic\FormBundle\Entity\Form;
use Mautic\FormBundle\Event\FormEvent;
use Mautic\FormBundle\EventListener\FormConditionalSubscriber;
use Mautic\FormBundle\Model\FieldModel;
use Mautic\FormBundle\Model\FormModel;
use PHPUnit\Framework\MockObject\MockObject;

final class FormConditionalSubscriberTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var MockObject|FormModel
     */
    private MockObject $formModel;

    /**
     * @var MockObject|FieldModel
     */
    private MockObject $fieldModel;

    private FormConditionalSubscriber $subscriber;

    public function setUp(): void
    {
        parent::setUp();
        $this->formModel  = $this->createMock(FormModel::class);
        $this->fieldModel = $this->createMock(FieldModel::class);
        $this->subscriber = new FormConditionalSubscriber(
            $this->formModel,
            $this->fieldModel
        );
    }

    public function testOnFormPostSaveForNewForm(): void
    {
        $parentField = $this->createMock(Field::class);
        $childField  = $this->createMock(Field::class);
        $parentId    = 'new_parent_id';
        $childId     = 'new_child_id';
        $form        = new Form();

        $parentField->method('getId')->willReturn($parentId);
        $parentField->method('getSessionId')->willReturn($parentId);
        $childField->method('getId')->willReturn($childId);
        $childField->method('getSessionId')->willReturn($childId);
        $childField->method('getParent')->willReturn($parentId);

        $form->addField(0, $parentField);
        $form->addField(1, $childField);

        $this->fieldModel->expects($this->once())
            ->method('saveEntity')
            ->with($parentField);

        $this->formModel->expects($this->never())
            ->method('deleteFields');

        $this->subscriber->onFormPostSave(new FormEvent($form, true));
    }

    /**
     * A child field should be deleted when the parent does not exist anymore.
     */
    public function testOnFormPostSaveForDeletedParent(): void
    {
        $childField  = $this->createMock(Field::class);
        $parentId    = 123;
        $childId     = 456;
        $form        = new Form();

        $childField->method('getId')->willReturn($childId);
        $childField->method('getSessionId')->willReturn($childId);
        $childField->method('getParent')->willReturn($parentId);

        $form->addField(1, $childField);

        $this->fieldModel->expects($this->never())
            ->method('saveEntity');

        $this->formModel->expects($this->once())
            ->method('deleteFields')
            ->with($form, [$childId]);

        $this->subscriber->onFormPostSave(new FormEvent($form, true));
    }
}
