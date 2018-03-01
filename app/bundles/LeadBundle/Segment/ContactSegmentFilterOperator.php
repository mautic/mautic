<?php

/*
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Segment;

use Mautic\LeadBundle\Event\LeadListFiltersOperatorsEvent;
use Mautic\LeadBundle\LeadEvents;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Class ContactSegmentFilterOperator.
 */
class ContactSegmentFilterOperator
{
    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;

    /**
     * @var OperatorOptions
     */
    private $operatorOptions;

    /**
     * ContactSegmentFilterOperator constructor.
     *
     * @param TranslatorInterface      $translator
     * @param EventDispatcherInterface $dispatcher
     * @param OperatorOptions          $operatorOptions
     */
    public function __construct(
        TranslatorInterface $translator,
        EventDispatcherInterface $dispatcher,
        OperatorOptions $operatorOptions
    ) {
        $this->translator      = $translator;
        $this->dispatcher      = $dispatcher;
        $this->operatorOptions = $operatorOptions;
    }

    /**
     * @param string $operator
     *
     * @return string
     */
    public function fixOperator($operator)
    {
        $options = $this->operatorOptions->getFilterExpressionFunctionsNonStatic();

        // Add custom filters operators
        $event = new LeadListFiltersOperatorsEvent($options, $this->translator);
        $this->dispatcher->dispatch(LeadEvents::LIST_FILTERS_OPERATORS_ON_GENERATE, $event);
        $options = $event->getOperators();

        $operatorDetails = $options[$operator];

        return $operatorDetails['expr'];
    }
}
