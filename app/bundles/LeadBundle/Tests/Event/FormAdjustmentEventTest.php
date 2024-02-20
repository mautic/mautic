<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Event;

use Mautic\LeadBundle\Event\FormAdjustmentEvent;
use Mautic\LeadBundle\Segment\OperatorOptions;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Form\FormInterface;

final class FormAdjustmentEventTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var MockObject&FormInterface<FormInterface<mixed>>
     */
    private \PHPUnit\Framework\MockObject\MockObject $form;

    protected function setUp(): void
    {
        parent::setUp();

        $this->form = $this->createMock(FormInterface::class);
    }

    public function testConstructGettersSetters(): void
    {
        $alias        = 'email';
        $object       = 'lead';
        $operator     = OperatorOptions::EQUAL_TO;
        $fieldDetails = [
            'properties' => [
                'type' => 'text',
                'list' => ['one', 'two'],
            ],
        ];
        $event = new FormAdjustmentEvent($this->form, $alias, $object, $operator, $fieldDetails);

        $this->assertSame($this->form, $event->getForm());
        $this->assertSame($alias, $event->getFieldAlias());
        $this->assertSame($object, $event->getFieldObject());
        $this->assertSame($operator, $event->getOperator());
        $this->assertSame($fieldDetails, $event->getFieldDetails());
        $this->assertSame('text', $event->getFieldType());
        $this->assertSame(['one', 'two'], $event->getFieldChoices());
        $this->assertFalse($event->filterShouldBeDisabled());
        $this->assertFalse($event->operatorIsOneOf(OperatorOptions::LESS_THAN));
        $this->assertFalse($event->operatorIsOneOf(OperatorOptions::LESS_THAN, OperatorOptions::NOT_EQUAL_TO));
        $this->assertTrue($event->operatorIsOneOf(OperatorOptions::LESS_THAN, OperatorOptions::EQUAL_TO));
        $this->assertFalse($event->fieldTypeIsOneOf('select'));
        $this->assertTrue($event->fieldTypeIsOneOf('select', 'text'));
    }
}
