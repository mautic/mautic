<?php

declare(strict_types=1);

namespace Mautic\PluginBundle\Tests\Form\Constraint;

use Mautic\PluginBundle\Event\PluginIsPublishedEvent;
use Mautic\PluginBundle\Form\Constraint\CanPublish;
use Mautic\PluginBundle\Form\Constraint\CanPublishValidator;
use Mautic\PluginBundle\PluginEvents;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Context\ExecutionContext;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class CanPublishValidatorTest extends TestCase
{
    /**
     * @var MockObject|EventDispatcherInterface
     */
    private $dispatcher;

    /**
     * @var MockObject|PluginIsPublishedEvent
     */
    private $event;
    private CanPublishValidator $canPublishValidator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->event      = $this->createMock(PluginIsPublishedEvent::class);

        $this->canPublishValidator = new CanPublishValidator($this->dispatcher);
    }

    public function testValidate(): void
    {
        $this->event->expects($this->once())
            ->method('isCanPublish')
            ->willReturn(false);

        $this->event->expects($this->once())
            ->method('getMessage')
            ->willReturn('Error in validation');

        $this->dispatcher->expects($this->once())
            ->method('dispatch')
            ->willReturn($this->event);

        $this->canPublishValidator->initialize($this->createMock(ExecutionContext::class));

        $this->canPublishValidator->validate(1, new CanPublish('testIntegration'));
    }

    public function testEventNotDispatchedIfUnpublished(): void
    {
        $this->event->expects($this->never())
            ->method('isCanPublish')
            ->willReturn(false);

        $this->event->expects($this->never())
            ->method('getMessage')
            ->willReturn('Error in validation');

        $this->dispatcher->expects($this->never())
            ->method('dispatch')
            ->with(PluginEvents::PLUGIN_IS_PUBLISHED_STATE_CHANGING)
            ->willReturn($this->event);

        $this->canPublishValidator->initialize($this->createMock(ExecutionContext::class));

        $this->canPublishValidator->validate(0, new CanPublish('testIntegration'));
    }

    public function testExceptionIsThrown(): void
    {
        $this->event->expects($this->never())
            ->method('isCanPublish')
            ->willReturn(false);

        $this->event->expects($this->never())
            ->method('getMessage')
            ->willReturn('Error in validation');

        $this->dispatcher->expects($this->never())
            ->method('dispatch')
            ->with(PluginEvents::PLUGIN_IS_PUBLISHED_STATE_CHANGING)
            ->willReturn($this->event);

        $this->canPublishValidator->initialize($this->createMock(ExecutionContext::class));

        $this->expectException(UnexpectedTypeException::class);

        $this->canPublishValidator->validate(1, new class extends Constraint {});
    }
}
