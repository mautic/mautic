<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Provider;

use Mautic\LeadBundle\Event\FieldOperatorsEvent;
use Mautic\LeadBundle\Event\TypeOperatorsEvent;
use Mautic\LeadBundle\LeadEvents;
use Mautic\LeadBundle\Provider\FilterOperatorProviderInterface;
use Mautic\LeadBundle\Provider\TypeOperatorProvider;
use Mautic\LeadBundle\Segment\OperatorOptions;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

final class TypeOperatorProviderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var MockObject|EventDispatcherInterface
     */
    private MockObject $dispatcher;

    /**
     * @var MockObject|FilterOperatorProviderInterface
     */
    private MockObject $filterOperatorPovider;

    private TypeOperatorProvider $provider;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dispatcher            = $this->createMock(EventDispatcherInterface::class);
        $this->filterOperatorPovider = $this->createMock(FilterOperatorProviderInterface::class);
        $this->provider              = new TypeOperatorProvider(
            $this->dispatcher,
            $this->filterOperatorPovider
        );
    }

    public function testGetOperatorsIncluding(): void
    {
        $this->filterOperatorPovider->expects($this->any())
            ->method('getAllOperators')
            ->willReturn([
                OperatorOptions::EQUAL_TO => [
                    'label'        => 'equals',
                    'expr'         => 'eq',
                    'negagte_expr' => 'neq',
                ],
                OperatorOptions::NOT_EQUAL_TO => [
                    'label'        => 'not equal',
                    'expr'         => 'neq',
                    'negagte_expr' => 'eq',
                ],
            ]);

        $this->assertSame(
            ['equals' => OperatorOptions::EQUAL_TO],
            $this->provider->getOperatorsIncluding([OperatorOptions::EQUAL_TO])
        );
    }

    public function testGetOperatorsExcluding(): void
    {
        $this->filterOperatorPovider->expects($this->any())
            ->method('getAllOperators')
            ->willReturn([
                OperatorOptions::EQUAL_TO => [
                    'label'        => 'equals',
                    'expr'         => 'eq',
                    'negagte_expr' => 'neq',
                ],
                OperatorOptions::NOT_EQUAL_TO => [
                    'label'        => 'not equal',
                    'expr'         => 'neq',
                    'negagte_expr' => 'eq',
                ],
            ]);

        $this->assertNotContains(
            OperatorOptions::EQUAL_TO,
            $this->provider->getOperatorsExcluding([OperatorOptions::EQUAL_TO])
        );
    }

    public function testGetOperatorsForFieldType(): void
    {
        $this->filterOperatorPovider->expects($this->any())
            ->method('getAllOperators')
            ->willReturn([
                OperatorOptions::EQUAL_TO => [
                    'label'        => 'equals',
                    'expr'         => 'eq',
                    'negagte_expr' => 'neq',
                ],
                OperatorOptions::NOT_EQUAL_TO => [
                    'label'        => 'not equal',
                    'expr'         => 'neq',
                    'negagte_expr' => 'eq',
                ],
                OperatorOptions::IN => [
                    'label'        => 'in',
                    'expr'         => 'in',
                    'negagte_expr' => 'notIn',
                ],
            ]);

        $this->dispatcher->expects($this->once())
            ->method('dispatch')
            ->with(
                $this->callback(function (TypeOperatorsEvent $event) {
                    // Emulate a subscriber.
                    $event->setOperatorsForFieldType('text', [
                        'include' => [
                            OperatorOptions::EQUAL_TO,
                            OperatorOptions::NOT_EQUAL_TO,
                        ],
                    ]);

                    return true;
                }),
                LeadEvents::COLLECT_OPERATORS_FOR_FIELD_TYPE
            );

        $this->assertSame(
            [
                'equals'    => OperatorOptions::EQUAL_TO,
                'not equal' => OperatorOptions::NOT_EQUAL_TO,
            ],
            $this->provider->getOperatorsForFieldType('text')
        );
    }

    public function testGetOperatorsForSpecificField(): void
    {
        $this->filterOperatorPovider->expects($this->any())
            ->method('getAllOperators')
            ->willReturn([
                OperatorOptions::EQUAL_TO => [
                    'label'        => 'equals',
                    'expr'         => 'eq',
                    'negagte_expr' => 'neq',
                ],
                OperatorOptions::NOT_EQUAL_TO => [
                    'label'        => 'not equal',
                    'expr'         => 'neq',
                    'negagte_expr' => 'eq',
                ],
                OperatorOptions::STARTS_WITH => [
                    'label'        => 'starts with',
                    'expr'         => 'startsWith',
                    'negagte_expr' => 'notStartsWith',
                ],
            ]);

        $this->dispatcher->expects($this->exactly(2))
            ->method('dispatch')
            ->withConsecutive(
                [
                    $this->callback(function (TypeOperatorsEvent $event) {
                        // Emulate a subscriber.
                        $event->setOperatorsForFieldType('text', [
                            'include' => [
                                OperatorOptions::EQUAL_TO,
                                OperatorOptions::NOT_EQUAL_TO,
                            ],
                        ]);

                        return true;
                    }),
                    LeadEvents::COLLECT_OPERATORS_FOR_FIELD_TYPE,
                ],
                [
                    $this->callback(function (FieldOperatorsEvent $event) {
                        // Emulate a subscriber.
                        $this->assertSame('text', $event->getType());
                        $this->assertSame('email', $event->getField());

                        // This is the important stuff. The Starts with opearator will be added.
                        $event->addOperator(OperatorOptions::STARTS_WITH);

                        return true;
                    }),
                    LeadEvents::COLLECT_OPERATORS_FOR_FIELD,
                ]
            );

        $this->assertSame(
            [
                'equals'      => OperatorOptions::EQUAL_TO,
                'not equal'   => OperatorOptions::NOT_EQUAL_TO,
                'starts with' => OperatorOptions::STARTS_WITH,
            ],
            $this->provider->getOperatorsForField('text', 'email')
        );
    }
}
