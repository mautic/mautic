<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Provider;

use Mautic\LeadBundle\Event\FormAdjustmentEvent;
use Mautic\LeadBundle\LeadEvents;
use Mautic\LeadBundle\Provider\FormAdjustmentsProvider;
use Mautic\LeadBundle\Segment\OperatorOptions;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormInterface;

final class FormAdjustmentsProviderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var MockObject&EventDispatcherInterface
     */
    private MockObject $dispatcher;

    /**
     * @var MockObject&FormInterface<mixed>
     */
    private MockObject $form;

    private FormAdjustmentsProvider $provider;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->form       = $this->createMock(FormInterface::class);
        $this->provider   = new FormAdjustmentsProvider($this->dispatcher);
    }

    public function testAdjustForm(): void
    {
        $this->dispatcher->expects($this->once())
            ->method('dispatch')
            ->with(
                $this->callback(function (FormAdjustmentEvent $event) {
                    $this->assertSame($this->form, $event->getForm());
                    $this->assertSame('email', $event->getFieldAlias());
                    $this->assertSame('lead', $event->getFieldObject());
                    $this->assertSame(OperatorOptions::EQUAL_TO, $event->getOperator());
                    $this->assertSame('text', $event->getFieldType());

                    return true;
                }),
                LeadEvents::ADJUST_FILTER_FORM_TYPE_FOR_FIELD
            );

        $this->provider->adjustForm(
            $this->form,
            'email',
            'lead',
            OperatorOptions::EQUAL_TO,
            ['properties' => ['type' => 'text']]
        );
    }
}
