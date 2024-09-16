<?php

namespace Mautic\LeadBundle\Entity;

use Mautic\LeadBundle\Segment\OperatorOptions;

trait OperatorListTrait
{
    /**
     * @var array<string, array<string, array<int, string>>>
     */
    protected $typeOperators = [
        'text' => [
            'include' => [
                OperatorOptions::EQUAL_TO,
                OperatorOptions::NOT_EQUAL_TO,
                OperatorOptions::EMPTY,
                OperatorOptions::NOT_EMPTY,
                OperatorOptions::LIKE,
                OperatorOptions::NOT_LIKE,
                OperatorOptions::REGEXP,
                OperatorOptions::NOT_REGEXP,
                OperatorOptions::STARTS_WITH,
                OperatorOptions::ENDS_WITH,
                OperatorOptions::CONTAINS,
            ],
        ],
        'select' => [
            'include' => [
                OperatorOptions::EQUAL_TO,
                OperatorOptions::NOT_EQUAL_TO,
                OperatorOptions::EMPTY,
                OperatorOptions::NOT_EMPTY,
                OperatorOptions::REGEXP,
                OperatorOptions::NOT_REGEXP,
                OperatorOptions::IN,
                OperatorOptions::NOT_IN,
            ],
        ],
        'bool' => [
            'include' => [
                OperatorOptions::EQUAL_TO,
                OperatorOptions::NOT_EQUAL_TO,
            ],
        ],
        'default' => [
            'exclude' => [
                OperatorOptions::IN,
                OperatorOptions::NOT_IN,
                OperatorOptions::DATE,
            ],
        ],
        'multiselect' => [
            'include' => [
                OperatorOptions::IN,
                OperatorOptions::NOT_IN,
                OperatorOptions::EMPTY,
                OperatorOptions::NOT_EMPTY,
            ],
        ],
        'date' => [
            'exclude' => [
                OperatorOptions::IN,
                OperatorOptions::NOT_IN,
            ],
        ],
        'lookup_id' => [
            'include' => [
                OperatorOptions::EQUAL_TO,
                OperatorOptions::NOT_EQUAL_TO,
                OperatorOptions::EMPTY,
                OperatorOptions::NOT_EMPTY,
            ],
        ],
        'number' => [
            'include' => [
                OperatorOptions::EQUAL_TO,
                OperatorOptions::NOT_EQUAL_TO,
                OperatorOptions::GREATER_THAN,
                OperatorOptions::GREATER_THAN_OR_EQUAL,
                OperatorOptions::LESS_THAN,
                OperatorOptions::LESS_THAN_OR_EQUAL,
                OperatorOptions::EMPTY,
                OperatorOptions::NOT_EMPTY,
                OperatorOptions::LIKE,
                OperatorOptions::NOT_LIKE,
                OperatorOptions::REGEXP,
                OperatorOptions::NOT_REGEXP,
                OperatorOptions::STARTS_WITH,
                OperatorOptions::ENDS_WITH,
                OperatorOptions::CONTAINS,
            ],
        ],
    ];

    /**
     * @deprecated to be removed in Mautic 3. Use FilterOperatorProvider::getAllOperators() instead.
     *
     * @param string|null $operator
     *
     * @return array<string,array<string,string>>|array<string,string>
     */
    public function getFilterExpressionFunctions($operator = null)
    {
        $operatorOption = OperatorOptions::getFilterExpressionFunctions();

        return (null === $operator) ? $operatorOption : $operatorOption[$operator];
    }

    /**
     * @param string|mixed[]|null $type
     * @param mixed[]             $overrideHiddenTypes
     *
     * @return mixed[]
     */
    public function getOperatorsForFieldType($type = null, $overrideHiddenTypes = [])
    {
        static $processedTypes = [];

        if (is_array($type)) {
            return $this->getOperatorChoiceList($type, $overrideHiddenTypes);
        } elseif (array_key_exists($type, $processedTypes)) {
            return $processedTypes[$type];
        }

        $this->normalizeType($type);

        if (null === $type) {
            foreach ($this->typeOperators as $type => $def) {
                if (!array_key_exists($type, $processedTypes)) {
                    $processedTypes[$type] = $this->getOperatorChoiceList($def, $overrideHiddenTypes);
                }
            }

            return $processedTypes;
        }

        $processedTypes[$type] = $this->getOperatorChoiceList($this->typeOperators[$type], $overrideHiddenTypes);

        return $processedTypes[$type];
    }

    /**
     * @param mixed[] $definition
     * @param mixed[] $overrideHiddenOperators
     *
     * @return mixed[]
     */
    public function getOperatorChoiceList($definition, $overrideHiddenOperators = [])
    {
        static $operatorChoices = [];
        if (empty($operatorChoices)) {
            $operatorList    = $this->getFilterExpressionFunctions();
            $operatorChoices = [];
            foreach ($operatorList as $operator => $def) {
                if (empty($def['hide']) || in_array($operator, $overrideHiddenOperators)) {
                    $operatorChoices[$operator] = $def['label'];
                }
            }
        }

        $choices = $operatorChoices;
        if (isset($definition['include'])) {
            // Inclusive operators
            $choices = array_intersect_key($choices, array_flip($definition['include']));
        } elseif (isset($definition['exclude'])) {
            // Exclusive operators
            $choices = array_diff_key($choices, array_flip($definition['exclude']));
        }

        if (isset($this->translator)) {
            foreach ($choices as $value => $label) {
                $choices[$value] = $this->translator->trans($label);
            }
        }

        return array_flip($choices);
    }

    /**
     * @deprecated These aliases are subscribed in the TypeOperatorSubscriber now so this is not necessary. To be removed in next Mautic version.
     *
     * Normalize type operator.
     *
     * @param string|null $type
     *
     * @return void
     */
    protected function normalizeType(&$type)
    {
        if (null === $type) {
            return;
        }

        if ('boolean' === $type) {
            $type = 'bool';
        } elseif (in_array($type, ['country', 'timezone', 'region', 'locale'])) {
            $type = 'select';
        } elseif (in_array($type, ['lookup',  'text', 'email', 'url', 'email', 'tel'])) {
            $type = 'text';
        } elseif ('datetime' === $type) {
            $type = 'date';
        } elseif (!array_key_exists($type, $this->typeOperators)) {
            $type = 'default';
        }
    }
}
