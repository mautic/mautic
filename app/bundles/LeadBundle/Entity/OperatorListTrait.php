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
    /**
     * @var array<string, array<string, array<int, string>>>
     */
    protected $typeOperators = [
        'text' => [
            'include' => [
                '=',
                '!=',
                'empty',
                '!empty',
                'like',
                '!like',
                'regexp',
                '!regexp',
                'startsWith',
                'endsWith',
                'contains',
            ],
        ],
        'select' => [
            'include' => [
                '=',
                '!=',
                'empty',
                '!empty',
                'regexp',
                '!regexp',
                'in',
                '!in',
            ],
        ],
        'bool' => [
            'include' => [
                '=',
                '!=',
            ],
        ],
        'default' => [
            'exclude' => [
                'in',
                '!in',
                'date',
            ],
        ],
        'multiselect' => [
            'include' => [
                'in',
                '!in',
                'empty',
                '!empty',
            ],
        ],
        'date' => [
            'exclude' => [
                'in',
                '!in',
            ],
        ],
        'lookup_id' => [
            'include' => [
                '=',
                '!=',
                'empty',
                '!empty',
            ],
        ],
    ];

    /**
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
