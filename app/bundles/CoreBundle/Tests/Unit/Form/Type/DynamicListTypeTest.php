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
     * @var MockObject&FormBuilderInterface
     */
    private MockObject $formBuilder;

    private DynamicListType $form;

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
                $this->callback(function ($formModifier): true {
                    $formEvent = $this->createMock(FormEvent::class);

                    $formEvent->expects($this->once())
                        ->method('getData')
                        ->willReturn(null);

                    $formEvent->expects($this->never())
                        ->method('setData');

                    $formModifier($formEvent);

                    return true;
                }),
                512
            );

        $this->form->buildForm($this->formBuilder, []);
    }

    public function testBuildFormWhenDataIsArray(): void
    {
        $this->formBuilder->expects($this->once())
            ->method('addEventListener')
            ->with(
                FormEvents::PRE_SUBMIT,
                $this->callback(function ($formModifier): true {
                    $formEvent = $this->createMock(FormEvent::class);
                    $data      = [['content' => 'dynamic slot content']];

                    $formEvent->expects($this->once())
                        ->method('getData')
                        ->willReturn($data);

                    $formEvent->expects($this->once())
                        ->method('setData')
                        ->with($data);

                    $formModifier($formEvent);

                    return true;
                }),
                512
            );

        $this->form->buildForm($this->formBuilder, []);
    }

    public function testPreSubmitRemovesStrayKeysAndReindexesEntries(): void
    {
        $listener = null;

        $this->formBuilder->expects($this->once())
            ->method('addEventListener')
            ->with(
                FormEvents::PRE_SUBMIT,
                $this->callback(function ($formModifier) use (&$listener): true {
                    $listener = $formModifier;

                    return true;
                }),
                512
            );

        $this->form->buildForm($this->formBuilder, []);

        $formEvent = $this->createMock(FormEvent::class);
        $formEvent->expects($this->once())
            ->method('getData')
            ->willReturn([
                'filter' => 'stray',
                0        => ['content' => 'first'],
                2        => ['content' => 'third'],
            ]);
        $formEvent->expects($this->once())
            ->method('setData')
            ->with([
                ['content' => 'first'],
                ['content' => 'third'],
            ]);

        $this->assertNotNull($listener);
        $listener($formEvent);
    }
}
