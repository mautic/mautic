<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Entity;

use Mautic\LeadBundle\Segment\OperatorOptions;

trait OperatorListTrait
{
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
    ];

    /**
     * @deprecated to be removed in Mautic 3. Use FilterOperatorProvider::getAllOperators() instead.
     * 
     * @param null $operator
     *
     * @return array
     */
    public function getFilterExpressionFunctions($operator = null)
    {
        $operatorOption = OperatorOptions::getFilterExpressionFunctions();

        return (null === $operator) ? $operatorOption : $operatorOption[$operator];
    }

    /**
     * @param string|array|null $type
     * @param array             $overrideHiddenTypes
     *
     * @return array
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
     * @param       $definition
     * @param array $overrideHiddenOperators
     *
     * @return array
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
     * Normalize type operator.
     *
     * @param $type
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
