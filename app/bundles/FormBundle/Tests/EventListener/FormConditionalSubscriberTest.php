<?php

declare(strict_types=1);

namespace Mautic\FormBundle\Tests\EventListener;

use Mautic\FormBundle\Entity\Field;
use Mautic\FormBundle\Entity\Form;
use Mautic\FormBundle\Event\FormEvent;
use Mautic\FormBundle\EventListener\FormConditionalSubscriber;
use Mautic\FormBundle\Model\FieldModel;
use Mautic\FormBundle\Model\FormModel;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;

#[AllowMockObjectsWithoutExpectations]
final class FormConditionalSubscriberTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var MockObject&FormModel
     */
    private MockObject $formModel;

    /**
     * @var MockObject&FieldModel
     */
    private MockObject $fieldModel;

    private FormConditionalSubscriber $subscriber;

    protected function setUp(): void
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
        $parentField = new Field();
        $childField  = new Field();
        $parentId    = 'new_parent_id';
        $childId     = 'new_child_id';
        $form        = new Form();

        (new \ReflectionProperty($parentField, 'id'))->setValue($parentField, $parentId);
        $parentField->setSessionId($parentId);
        (new \ReflectionProperty($childField, 'id'))->setValue($childField, $childId);
        $childField->setSessionId($childId);
        $childField->setParent($parentId);

        $form->addField(0, $parentField);
        $form->addField(1, $childField);

        $this->fieldModel->expects($this->once())
            ->method('saveEntity')
            ->with($childField);

        $this->formModel->expects($this->never())
            ->method('deleteFields');

        $this->subscriber->onFormPostSave(new FormEvent($form, true));
    }

    /**
     * A child field should be deleted when the parent does not exist anymore.
     */
    public function testOnFormPostSaveForDeletedParent(): void
    {
        $childField  = new Field();
        $parentId    = 123;
        $childId     = 456;
        $form        = new Form();

        (new \ReflectionProperty($childField, 'id'))->setValue($childField, $childId);
        $childField->setSessionId($childId);
        $childField->setParent((string) $parentId);

        $form->addField(1, $childField);

        $this->fieldModel->expects($this->never())
            ->method('saveEntity');

        $this->formModel->expects($this->once())
            ->method('deleteFields')
            ->with($form, [$childId]);

        $this->subscriber->onFormPostSave(new FormEvent($form, true));
    }
}
