<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Unit\Form\Type;

use Mautic\CoreBundle\Form\Type\DynamicListType;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

final class DynamicListTypeTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var MockObject&FormBuilderInterface<FormBuilderInterface>
     */
    private $formBuilder;

    /**
     * @var DynamicListType
     */
    private $form;

    protected function setUp(): void
    {
        parent::setUp();

        $this->formBuilder = $this->createMock(FormBuilderInterface::class);
        $this->form        = new DynamicListType();
    }

    public function testBuildFormWhenDataIsNull(): void
    {
        $this->formBuilder->expects($this->once())
            ->method('addEventListener')
            ->with(
                FormEvents::PRE_SUBMIT,
                $this->callback(function ($formModifier) {
                    $formEvent = $this->createMock(FormEvent::class);

                    $formEvent->expects($this->once())
                        ->method('getData')
                        ->willReturn(null);

                    $formEvent->expects($this->never())
                        ->method('setData');

                    $formModifier($formEvent);

                    return true;
                })
            );

        $this->form->buildForm($this->formBuilder, []);
    }

    public function testBuildFormWhenDataIsArray(): void
    {
        $this->formBuilder->expects($this->once())
            ->method('addEventListener')
            ->with(
                FormEvents::PRE_SUBMIT,
                $this->callback(function ($formModifier) {
                    $formEvent = $this->createMock(FormEvent::class);
                    $data = [['content' => 'dynamic slot content']];

                    $formEvent->expects($this->once())
                        ->method('getData')
                        ->willReturn($data);

                    $formEvent->expects($this->once())
                        ->method('setData')
                        ->with($data);

                    $formModifier($formEvent);

                    return true;
                })
            );

        $this->form->buildForm($this->formBuilder, []);
    }
}
