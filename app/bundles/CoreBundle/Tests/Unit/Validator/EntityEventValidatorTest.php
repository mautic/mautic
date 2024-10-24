<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Unit\Validator;

use Mautic\CoreBundle\Event\EntityValidateEvent;
use Mautic\CoreBundle\Validator\EntityEvent;
use Mautic\CoreBundle\Validator\EntityEventValidator;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\ConstraintValidatorInterface;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class EntityEventValidatorTest extends TestCase
{
    private EventDispatcherInterface $dispatcher;
    private ExecutionContextInterface $context;
    private ConstraintValidatorInterface $validator;

    protected function setUp(): void
    {
        $this->context    = $this->createMock(ExecutionContextInterface::class);
        $this->dispatcher = new EventDispatcher();
        $this->validator  = new EntityEventValidator($this->dispatcher);
        $this->validator->initialize($this->context);
    }

    public function testInvalidValue(): void
    {
        $this->expectException(UnexpectedTypeException::class);
        $this->expectExceptionMessage('Expected argument of type "object", "string" given');

        $this->validator->validate('invalidType', new EntityEvent());
    }

    public function testInvalidConstraint(): void
    {
        $this->expectException(UnexpectedTypeException::class);
        $this->expectExceptionMessageMatches('/Expected argument of type "Mautic\\\CoreBundle\\\Validator\\\EntityEvent"/');

        $this->validator->validate(new \stdClass(), new NotBlank());
    }

    public function testEventIsDispatched(): void
    {
        $dispatched = false;
        $entity     = new \stdClass();
        $constraint = new EntityEvent();

        $this->dispatcher->addListener(EntityValidateEvent::class, function (EntityValidateEvent $event) use (&$dispatched, $entity, $constraint) {
            $dispatched = true;

            Assert::assertSame($entity, $event->getEntity());
            Assert::assertSame($constraint, $event->getConstraint());
            Assert::assertSame($this->context, $event->getContext());
        });

        $this->validator->validate($entity, $constraint);

        Assert::assertTrue($dispatched);
    }
}
