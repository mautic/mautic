<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Provider;

use Mautic\LeadBundle\Event\FieldOperatorsEvent;
use Mautic\LeadBundle\Event\OverrideOperatorLabelEvent;
use Mautic\LeadBundle\Event\TypeOperatorsEvent;
use Mautic\LeadBundle\LeadEvents;
use Mautic\LeadBundle\Provider\FilterOperatorProviderInterface;
use Mautic\LeadBundle\Provider\TypeOperatorProvider;
use Mautic\LeadBundle\Segment\OperatorOptions;
use PHPUnit\Framework\MockObject\MockObject;
<<<<<<< HEAD
=======
use PHPUnit\Framework\TestCase;
>>>>>>> b6a6112223 (Merge pull request #2190 from acquia/MAUT-11442)
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

final class TypeOperatorProviderTest extends \PHPUnit\Framework\TestCase
{
<<<<<<< HEAD
    /**
     * @var MockObject|EventDispatcherInterface
     */
    private MockObject $dispatcher;

    /**
     * @var MockObject|FilterOperatorProviderInterface
     */
    private MockObject $filterOperatorPovider;
=======
    private MockObject|EventDispatcherInterface $dispatcher;

    private MockObject|FilterOperatorProviderInterface $filterOperatorProvider;
>>>>>>> b6a6112223 (Merge pull request #2190 from acquia/MAUT-11442)

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
                OperatorOptions::INCLUDING_ANY => [
                    'label'        => 'in',
                    'expr'         => 'in',
                    'negagte_expr' => 'notIn',
                ],
            ]);

        $this->dispatcher->expects($this->exactly(2))
            ->method('dispatch')
<<<<<<< HEAD
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
=======
            ->withConsecutive(
                [
                    LeadEvents::COLLECT_OPERATORS_FOR_FIELD_TYPE,
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
                ],
                [
                    LeadEvents::OVERRIDE_OPERATOR_LABEL_FOR_FIELD_TYPE,
                    $this->isInstanceOf(OverrideOperatorLabelEvent::class),
                ],
>>>>>>> b6a6112223 (Merge pull request #2190 from acquia/MAUT-11442)
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
        $matcher = $this->exactly(2);

<<<<<<< HEAD
        $this->dispatcher->expects($matcher)
            ->method('dispatch')->willReturnCallback(function (...$parameters) use ($matcher) {
                if (1 === $matcher->numberOfInvocations()) {
                    $callback = function (TypeOperatorsEvent $event) {
=======
        $this->dispatcher->expects($this->exactly(3))
            ->method('dispatch')
            ->withConsecutive(
                [
                    LeadEvents::COLLECT_OPERATORS_FOR_FIELD_TYPE,
                    $this->callback(function (TypeOperatorsEvent $event) {
>>>>>>> b6a6112223 (Merge pull request #2190 from acquia/MAUT-11442)
                        // Emulate a subscriber.
                        $event->setOperatorsForFieldType('text', [
                            'include' => [
                                OperatorOptions::EQUAL_TO,
                                OperatorOptions::NOT_EQUAL_TO,
                            ],
                        ]);
<<<<<<< HEAD
                    };
                    $callback($parameters[0]);
                    $this->assertSame(LeadEvents::COLLECT_OPERATORS_FOR_FIELD_TYPE, $parameters[1]);
                }
                if (2 === $matcher->numberOfInvocations()) {
                    $callback = function (FieldOperatorsEvent $event) {
=======

                        return true;
                    }),
                ],
                [
                    LeadEvents::OVERRIDE_OPERATOR_LABEL_FOR_FIELD_TYPE,
                    $this->isInstanceOf(OverrideOperatorLabelEvent::class),
                ],
                [
                    LeadEvents::COLLECT_OPERATORS_FOR_FIELD,
                    $this->callback(function (FieldOperatorsEvent $event) {
>>>>>>> b6a6112223 (Merge pull request #2190 from acquia/MAUT-11442)
                        // Emulate a subscriber.
                        $this->assertSame('text', $event->getType());
                        $this->assertSame('email', $event->getField());

                        // This is the important stuff. The Starts with opearator will be added.
                        $event->addOperator(OperatorOptions::STARTS_WITH);
                    };
                    $callback($parameters[0]);
                    $this->assertSame(LeadEvents::COLLECT_OPERATORS_FOR_FIELD, $parameters[1]);
                }

<<<<<<< HEAD
                return $parameters[0];
            });
=======
                        return true;
                    }),
                ],
            );
>>>>>>> b6a6112223 (Merge pull request #2190 from acquia/MAUT-11442)

        $this->assertSame(
            [
                'equals'      => OperatorOptions::EQUAL_TO,
                'not equal'   => OperatorOptions::NOT_EQUAL_TO,
                'starts with' => OperatorOptions::STARTS_WITH,
            ],
            $this->provider->getOperatorsForField('text', 'email')
        );
    }

    public function testGetOperatorsForFieldTypeForDate(): void
    {
        $this->filterOperatorProvider->expects($this->any())
            ->method('getAllOperators')
            ->willReturn([
                OperatorOptions::GREATER_THAN => [
                    'label'       => 'grater than',
                    'expr'        => 'gt',
                    'negate_expr' => 'lt',
                ],
                OperatorOptions::GREATER_THAN_OR_EQUAL => [
                    'label'       => 'grater than or equal',
                    'expr'        => 'gte',
                    'negate_expr' => 'lt',
                ],
                OperatorOptions::LESS_THAN => [
                    'label'        => 'less than',
                    'expr'         => 'lt',
                    'negagte_expr' => 'gt',
                ],
                OperatorOptions::LESS_THAN_OR_EQUAL => [
                    'label'       => 'less than or equal',
                    'expr'        => 'lte',
                    'negate_expr' => 'gt',
                ],
            ]);

        $this->dispatcher->expects($this->exactly(2))
            ->method('dispatch')
            ->withConsecutive(
                [
                    LeadEvents::COLLECT_OPERATORS_FOR_FIELD_TYPE,
                    $this->callback(function (TypeOperatorsEvent $event) {
                        // Emulate a subscriber.
                        $event->setOperatorsForFieldType('date', [
                            'include' => [
                                OperatorOptions::GREATER_THAN,
                                OperatorOptions::GREATER_THAN_OR_EQUAL,
                                OperatorOptions::LESS_THAN,
                                OperatorOptions::LESS_THAN_OR_EQUAL,
                            ],
                        ]);

                        return true;
                    }),
                ],
                [
                    LeadEvents::OVERRIDE_OPERATOR_LABEL_FOR_FIELD_TYPE,
                    $this->callback(function (OverrideOperatorLabelEvent $event) {
                        // Emulate a subscriber.
                        $event->setTypeOperatorsChoices(
                            [
                                'After'                  => OperatorOptions::GREATER_THAN,
                                'After (Including day)'  => OperatorOptions::GREATER_THAN_OR_EQUAL,
                                'Before'                 => OperatorOptions::LESS_THAN,
                                'Before (Including day)' => OperatorOptions::LESS_THAN_OR_EQUAL,
                            ]
                        );

                        return true;
                    }),
                ],
            );

        $this->assertSame(
            [
                'After'                  => OperatorOptions::GREATER_THAN,
                'After (Including day)'  => OperatorOptions::GREATER_THAN_OR_EQUAL,
                'Before'                 => OperatorOptions::LESS_THAN,
                'Before (Including day)' => OperatorOptions::LESS_THAN_OR_EQUAL,
            ],
            $this->provider->getOperatorsForFieldType('date')
        );
    }

    public function testGetOperatorsIncludingWithFieldType(): void
    {
        $this->filterOperatorProvider->expects($this->any())
            ->method('getAllOperators')
            ->willReturn([
                OperatorOptions::GREATER_THAN => [
                    'label'       => 'grater than',
                    'expr'        => 'gt',
                    'negate_expr' => 'lt',
                ],
                OperatorOptions::GREATER_THAN_OR_EQUAL => [
                    'label'       => 'grater than or equal',
                    'expr'        => 'gte',
                    'negate_expr' => 'lt',
                ],
                OperatorOptions::LESS_THAN => [
                    'label'        => 'less than',
                    'expr'         => 'lt',
                    'negagte_expr' => 'gt',
                ],
                OperatorOptions::LESS_THAN_OR_EQUAL => [
                    'label'       => 'less than or equal',
                    'expr'        => 'lte',
                    'negate_expr' => 'gt',
                ],
            ]);

        $this->dispatcher->expects($this->once())
            ->method('dispatch')
            ->with(
                LeadEvents::OVERRIDE_OPERATOR_LABEL_FOR_FIELD_TYPE,
                $this->callback(function (OverrideOperatorLabelEvent $event) {
                    // Emulate a subscriber.
                    $event->setTypeOperatorsChoices(
                        [
                            'After'                  => OperatorOptions::GREATER_THAN,
                            'After (Including day)'  => OperatorOptions::GREATER_THAN_OR_EQUAL,
                            'Before'                 => OperatorOptions::LESS_THAN,
                            'Before (Including day)' => OperatorOptions::LESS_THAN_OR_EQUAL,
                        ]
                    );

                    return true;
                }),
            );

        $this->assertSame(
            [
                'After'                  => OperatorOptions::GREATER_THAN,
                'After (Including day)'  => OperatorOptions::GREATER_THAN_OR_EQUAL,
                'Before'                 => OperatorOptions::LESS_THAN,
                'Before (Including day)' => OperatorOptions::LESS_THAN_OR_EQUAL,
            ],
            $this->provider->getOperatorsIncludingFieldType([
                OperatorOptions::GREATER_THAN,
                OperatorOptions::GREATER_THAN_OR_EQUAL,
                OperatorOptions::LESS_THAN,
                OperatorOptions::LESS_THAN_OR_EQUAL,
            ], 'date')
        );
    }
}
