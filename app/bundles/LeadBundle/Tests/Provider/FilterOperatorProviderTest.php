<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Provider;

use Mautic\LeadBundle\Event\LeadListFiltersOperatorsEvent;
use Mautic\LeadBundle\LeadEvents;
use Mautic\LeadBundle\Provider\FilterOperatorProvider;
use Mautic\LeadBundle\Segment\OperatorOptions;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Translation\TranslatorInterface;

final class FilterOperatorProviderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var MockObject|EventDispatcherInterface
     */
    private $dispatcher;

    /**
     * @var MockObject|TranslatorInterface
     */
    private $translator;

    /**
     * @var FilterOperatorProvider
     */
    private $provider;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->provider   = new FilterOperatorProvider(
            $this->dispatcher,
            $this->translator
        );
    }

    public function testGetAllOperators(): void
    {
        $this->dispatcher->expects($this->once())
            ->method('dispatch')
            ->with(
                LeadEvents::LIST_FILTERS_OPERATORS_ON_GENERATE,
                $this->callback(function (LeadListFiltersOperatorsEvent $event) {
                    // Emulate a subscriber.
                    $event->addOperator(
                        OperatorOptions::EQUAL_TO,
                        [
                            'label'       => 'equals to',
                            'expr'        => 'eq',
                            'negate_expr' => 'neq',
                        ]
                    );

                    return true;
                })
            );

        $this->translator->expects($this->once())
            ->method('trans')
            ->with('equals to')
            ->willReturnArgument(0);

        // Call it for the first time so the class cache would be populated.
        $this->provider->getAllOperators();

        // Call it for the second time to ensure the cache is used and the event is fired only once.
        $operators = $this->provider->getAllOperators();

        $this->assertSame([
            OperatorOptions::EQUAL_TO => [
                'label'       => 'equals to',
                'expr'        => 'eq',
                'negate_expr' => 'neq',
            ],
        ], $operators);
    }
}
