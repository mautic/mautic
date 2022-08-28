<?php

namespace Mautic\LeadBundle\Event;

use Mautic\CoreBundle\Event\CommonEvent;
use Symfony\Component\Translation\TranslatorInterface;

class LeadListFiltersOperatorsEvent extends CommonEvent
{
    /**
     * Please refer to OperatorListTrait.php, inside getFilterExpressionFunctions method, for examples of operators.
     *
     * @var array
     */
    protected $operators;

    /**
     * @deprecated to be removed in Mautic 3
     *
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @deprecated to be removed in Mautic 3
     *
     * @param array               $operators  @deprecated to be removed in Mautic 3. Subscribe operators instead.
     * @param TranslatorInterface $translator @deprecated to be removed in Mautic 3
     */
    public function __construct($operators, TranslatorInterface $translator)
    {
        $this->operators  = $operators;
        $this->translator = $translator;
    }

    /**
     * @return array
     */
    public function getOperators()
    {
        return $this->operators;
    }

    /**
     * @deprecated to be removed in Mautic 3. Use DI instead.
     *
     * @return TranslatorInterface
     */
    public function getTranslator()
    {
        return $this->translator;
    }

    /**
     * Add a new operator for list filters
     * Please refer to OperatorListTrait.php, inside getFilterExpressionFunctions method, for examples of operators.
     *
     * @see OperatorListTrait
     *
     * @param string $operatorKey
     * @param array  $operatorConfig
     */
    public function addOperator($operatorKey, $operatorConfig)
    {
        if (!array_key_exists($operatorKey, $this->operators)) {
            $this->operators[$operatorKey] = $operatorConfig;
        }
    }
}
