<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Event;

use Mautic\CoreBundle\Event\AbstractCustomRequestEvent;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * The event is dispatched when the choices for campaign segment filters are generated.
 */
class CompanySegmentFiltersChoicesEvent extends AbstractCustomRequestEvent
{
    /**
     * @param array<array<array<mixed>>> $choices
     * @param array<mixed>               $operators
     */
    public function __construct(
        private array $choices,
        private array $operators,
        protected TranslatorInterface $translator,
        ?Request $request = null,
        private string $search = '',
    ) {
        parent::__construct($request);
    }

    /**
     * @return array<array<array<mixed>>>
     */
    public function getChoices(): array
    {
        return $this->choices;
    }

    /**
     * @return mixed[]
     */
    public function getOperators(): array
    {
        return $this->operators;
    }

    public function getTranslator(): TranslatorInterface
    {
        return $this->translator;
    }

    public function getSearch(): string
    {
        return $this->search;
    }

    /**
     * Add a new choice for campaign segment filters
     * Please refer to ListModel.php, inside getChoiceFields method, for examples of choices.
     *
     * @param mixed[] $choiceConfig
     */
    public function addChoice(string $object, string $choiceKey, array $choiceConfig): void
    {
        if (!isset($this->choices[$object])) {
            $this->choices[$object] = [];
        }
        if (!array_key_exists($choiceKey, $this->choices[$object])) {
            $this->choices[$object][$choiceKey] = $choiceConfig;
        }
    }

    /**
     * @param mixed[] $choiceConfig
     */
    public function setChoice(string $object, string $choiceKey, array $choiceConfig): void
    {
        if (!isset($this->choices[$object])) {
            $this->choices[$object] = [];
        }

        $this->choices[$object][$choiceKey] = $choiceConfig;
    }

    /**
     * @param array<string,array<string,array<string, mixed>>> $choices
     */
    public function setChoices(array $choices): void
    {
        $this->choices = $choices;
    }

    /**
     * Add a new operator for list filters
     * Please refer to OperatorListTrait.php, inside getFilterExpressionFunctions method, for examples of operators.
     *
     * @param array<mixed> $operatorConfig
     *
     * @see OperatorListTrait
     */
    public function addOperator(string $operatorKey, array $operatorConfig): void
    {
        if (!array_key_exists($operatorKey, $this->operators)) {
            $this->operators[$operatorKey] = $operatorConfig;
        }
    }
}
