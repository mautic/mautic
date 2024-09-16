<?php

namespace Mautic\CoreBundle\Form\ChoiceLoader;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\Expression\CompositeExpression;
use Doctrine\DBAL\Query\Expression\ExpressionBuilder;
use Mautic\CoreBundle\Factory\ModelFactory;
use Mautic\CoreBundle\Model\AjaxLookupModelInterface;
use Symfony\Component\Form\ChoiceList\ArrayChoiceList;
use Symfony\Component\Form\ChoiceList\ChoiceListInterface;
use Symfony\Component\Form\ChoiceList\Loader\ChoiceLoaderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Contracts\Translation\TranslatorInterface;

class EntityLookupChoiceLoader implements ChoiceLoaderInterface
{
    /**
     * @var array
     */
    protected $selected = [];

    /**
     * @var array
     */
    protected $choices = [];

    /**
     * @param ModelFactory<object> $modelFactory
     * @param array                $options
     */
    public function __construct(
        protected ModelFactory $modelFactory,
        protected TranslatorInterface $translator,
        protected Connection $connection,
        protected $options = []
    ) {
    }

    /**
     * @param Options|array $options
     */
    public function setOptions($options): void
    {
        $this->options = $options;
    }

    public function loadChoiceList($value = null): ChoiceListInterface
    {
        return new ArrayChoiceList($this->getChoices(null, true));
    }

    /**
     * Validate submitted values.
     *
     * Convert to other data types to strings - we're already working with IDs so just return $values
     */
    public function loadChoicesForValues(array $values, $value = null): array
    {
        return $values;
    }

    /**
     * Convert to other data types to strings - we're already working with IDs so just return $choices.
     */
    public function loadValuesForChoices(array $choices, $value = null): array
    {
        return $choices;
    }

    /**
     * Take note of the selected values for loadChoiceList.
     */
    public function onFormPostSetData(FormEvent $event): void
    {
        $this->selected = (array) $event->getData();
    }

    /**
     * @param array|null $data
     * @param bool       $includeNew
     *
     * @return array
     */
    protected function getChoices($data = null, $includeNew = false)
    {
        if (null === $data) {
            $data = $this->selected;
        }

        $modelName  = $this->options['model'];
        $modalRoute = $this->options['modal_route'];

        // Check if we've already f the choices
        if (!isset($this->choices[$modelName]) || count(array_diff($data, array_keys($this->choices[$modelName]))) !== count($data)) {
            $this->choices[$modelName] = [];

            if ($data) {
                $data = array_map(
                    fn ($v): int => (int) $v,
                    (array) $data
                );
            }

            // Build choice list in case of different formats
            $choices = $this->fetchChoices($modelName, $data);

            if ($choices) {
                $this->formatChoices($choices);
            }

            if ($includeNew && !empty($data)) {
                // Fetch some extra choices
                $extraChoices = $this->fetchChoices($modelName);

                // Test if grouped
                if ($extraChoices) {
                    $this->formatChoices($extraChoices);
                    foreach ($extraChoices as $k => $v) {
                        if (is_array($v)) {
                            if (!isset($choices[$k])) {
                                $choices[$k] = $v;
                            } else {
                                $choices[$k] = array_replace($choices[$k], $v);
                            }
                        } else {
                            $choices[$k] = $v;
                        }
                    }
                }
                unset($extraChoices);
            }

            $this->choices[$modelName] = $choices;
        }

        // must be [$label => $id]
        $prepped      = $this->prepareChoices($this->choices[$modelName]);
        $prepped_keys = array_keys($prepped);

        array_multisort($prepped_keys, SORT_NATURAL | SORT_FLAG_CASE, $prepped);

        if ($includeNew && $modalRoute) {
            $prepped = array_replace([$this->translator->trans('mautic.core.createnew') => 'new'], $prepped);
        }

        return $prepped;
    }

    /**
     * @return array
     */
    protected function prepareChoices($choices)
    {
        $prepped   = $choices;
        $isGrouped = false;
        foreach ($prepped as &$choice) {
            if (is_array($choice)) {
                $isGrouped = true;
                $choice    = $this->prepareChoices($choice);
            }
        }

        if (!$isGrouped) {
            // fix for array_count_values error when there are null values
            $prepped = array_replace($prepped, array_fill_keys(array_keys($prepped, null), ''));
            // Same labels will cause options to be merged with Symfony 2.8+ so ensure labels are unique
            $counts     = array_count_values($prepped);
            $duplicates = array_filter(
                $prepped,
                fn ($value): bool => $counts[$value] > 1
            );

            if (count($duplicates)) {
                foreach ($duplicates as $value => $label) {
                    $prepped[$value] = "$label ($value)";
                }
            }

            $prepped = array_flip($prepped);
        }

        return $prepped;
    }

    /**
     * @return array|mixed
     */
    protected function fetchChoices($modelName, $data = [])
    {
        $labelColumn = $this->options['entity_label_column'];
        $idColumn    = $this->options['entity_id_column'];

        if (!$this->modelFactory->hasModel($modelName)) {
            throw new \InvalidArgumentException("$modelName not found as a registered model service.");
        }
        $model = $this->modelFactory->getModel($modelName);
        if (!$model instanceof AjaxLookupModelInterface) {
            throw new \InvalidArgumentException($model::class.' must implement '.AjaxLookupModelInterface::class);
        }

        $args = $this->options['lookup_arguments'] ?? [];
        if ($dataPlaceholder = array_search('$data', $args)) {
            $args[$dataPlaceholder] = $data;
        }

        // Default to 100 records if no data is populated
        if (empty($data) && isset($args['limit'])) {
            $args['limit'] = 100;
        }

        // Check if the method exists in the model
        $methodName = $this->options['model_lookup_method'] ?? null;
        if ($methodName && method_exists($model, $methodName)) {
            $choices = call_user_func_array([$model, $this->options['model_lookup_method']], $args);
        } elseif (isset($this->options['repo_lookup_method'])) {
            $choices = call_user_func_array([$model->getRepository(), $this->options['repo_lookup_method']], $args);
        } else {
            // rewrite query to use expression builder
            $alias     = $model->getRepository()->getTableAlias();
            $expr      = new ExpressionBuilder($this->connection);
            $composite = null;

            $limit = 100;
            if ($data) {
                $composite = CompositeExpression::and($expr->in($alias.'.id', $data));

                if (count($data) > $limit) {
                    $limit = count($data);
                }
            }

            $choices = $model->getRepository()->getSimpleList($composite, [], $labelColumn, $idColumn, null, $limit);
        }

        return $choices;
    }

    protected function formatChoices(array &$choices)
    {
        $firstKey = array_key_first($choices);

        if (is_array($choices[$firstKey])) {
            $validChoices = [];

            // Check if this is value/label formatted
            if (!array_key_exists('value', $choices[$firstKey])) {
                // Grouped choices so check the first key of the first group
                foreach ($choices as $groupKey => $groupChoices) {
                    $validChoices[$groupKey] = [];
                    if (!empty($groupChoices)) {
                        foreach ($groupChoices as $label => $choice) {
                            // Grouped values are keyed by label on purpose
                            if (is_array($choice) && array_key_exists('value', $choice)) {
                                $validChoices[$groupKey][$choice['label']] = $choice['choice'];
                            } else {
                                $validChoices[$groupKey][$label] = $choice;
                            }
                        }
                    }
                }
            } else {
                foreach ($choices as $choice) {
                    // Non-grouped are keyed by value on purpose
                    $validChoices[$choice['value']] = $choice['label'];
                }
            }

            $choices = $validChoices;
        }
    }
}
