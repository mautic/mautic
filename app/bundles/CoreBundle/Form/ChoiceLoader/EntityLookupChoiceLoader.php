<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Form\ChoiceLoader;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\Expression\ExpressionBuilder;
use Mautic\CoreBundle\Factory\ModelFactory;
use Mautic\CoreBundle\Model\AjaxLookupModelInterface;
use Mautic\CoreBundle\Translation\Translator;
use Symfony\Component\Form\ChoiceList\ArrayChoiceList;
use Symfony\Component\Form\ChoiceList\Loader\ChoiceLoaderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Class EntityLookupChoiceLoader.
 */
class EntityLookupChoiceLoader implements ChoiceLoaderInterface
{
    /**
     * @var array
     */
    protected $selected;

    /**
     * @var array
     */
    protected $choices = [];

    /**
     * @var Options
     */
    protected $options;

    /**
     * @var ModelFactory
     */
    protected $modelFactory;

    /**
     * @var Translator
     */
    protected $translator;

    /**
     * @var Connection
     */
    protected $connection;

    /**
     * EntityLookupChoiceLoader constructor.
     *
     * @param ModelFactory        $modelFactory
     * @param TranslatorInterface $translator
     * @param Connection          $connection
     * @param array               $options
     */
    public function __construct(ModelFactory $modelFactory, TranslatorInterface $translator, Connection $connection, $options = [])
    {
        $this->modelFactory = $modelFactory;
        $this->translator   = $translator;
        $this->connection   = $connection;
        $this->options      = $options;
    }

    /**
     * @param Options|array $options
     */
    public function setOptions($options)
    {
        $this->options = $options;
    }

    /**
     * @param null $value
     *
     * @return ArrayChoiceList
     */
    public function loadChoiceList($value = null)
    {
        return new ArrayChoiceList($this->getChoices(null, true));
    }

    /**
     * Validate submitted values.
     *
     * Convert to other data types to strings - we're already working with IDs so just return $values
     *
     * @param array $values
     * @param null  $value
     *
     * @return array
     */
    public function loadChoicesForValues(array $values, $value = null)
    {
        return $values;
    }

    /**
     * Convert to other data types to strings - we're already working with IDs so just return $choices.
     *
     * @param array $choices
     * @param null  $value
     *
     * @return array
     */
    public function loadValuesForChoices(array $choices, $value = null)
    {
        return $choices;
    }

    /**
     * Take note of the selected values for loadChoiceList.
     *
     * @param FormEvent $event
     */
    public function onFormPostSetData(FormEvent $event)
    {
        $this->selected = $event->getData();
    }

    /**
     * @param null $data
     * @param bool $includeNew
     *
     * @return array
     */
    protected function getChoices($data = null, $includeNew = false)
    {
        if (null == $data) {
            $data = $this->selected;
        }

        $modelName  = $this->options['model'];
        $modalRoute = $this->options['modal_route'];

        // Check if we've already f the choices
        if (!isset($this->choices[$modelName]) || count(array_diff($data, array_keys($this->choices[$modelName]))) !== count($data)) {
            $this->choices[$modelName] = [];

            if ($data) {
                $data = array_map(
                    function ($v) {
                        return (int) $v;
                    },
                    (array) $data
                );
            }

            // Build choice list in case of different formats
            $choices = $this->fetchChoices($modelName, $data);

            if ($includeNew && !empty($data)) {
                // Fetch some extra choices
                $extraChoices = $this->fetchChoices($modelName);

                // Test if grouped
                if ($extraChoices) {
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

            if (!empty($choices)) {
                $this->formatChoices($choices);
            }

            $this->choices[$modelName] = $choices;
        }

        // must be [$label => $id]
        $prepped = $this->prepareChoices($this->choices[$modelName]);

        array_multisort(array_keys($prepped), SORT_NATURAL | SORT_FLAG_CASE, $prepped);

        if ($includeNew && $modalRoute) {
            $prepped = array_replace([$this->translator->trans('mautic.core.createnew') => 'new'], $prepped);
        }

        return $prepped;
    }

    /**
     * @param $choices
     *
     * @return array
     */
    protected function prepareChoices($choices)
    {
        $prepped   = $choices;
        $isGrouped = false;
        foreach ($prepped as $key => &$choice) {
            if (is_array($choice)) {
                $isGrouped = true;
                $choice    = $this->prepareChoices($choice);
            }
        }

        if (!$isGrouped) {
            // Same labels will cause options to be merged with Symfony 2.8+ so ensure labels are unique
            $counts     = array_count_values($prepped);
            $duplicates = array_filter(
                $prepped,
                function ($value) use ($counts) {
                    return $counts[$value] > 1;
                }
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
     * @param $modelName
     * @param $data
     *
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
            throw new \InvalidArgumentException(get_class($model).' must implement '.AjaxLookupModelInterface::class);
        }

        $args = (isset($this->options['lookup_arguments'])) ? $this->options['lookup_arguments'] : [];
        if ($dataPlaceholder = array_search('$data', $args)) {
            $args[$dataPlaceholder] = $data;
        }

        // Default to 100 records if no data is populated
        if (empty($data) && isset($args['limit'])) {
            $args['limit'] = 100;
        }

        if (isset($this->options['model_lookup_method'])) {
            $choices = call_user_func_array([$model, $this->options['model_lookup_method']], $args);
        } elseif (isset($this->options['repo_lookup_method'])) {
            $choices = call_user_func_array([$model->getRepository(), $this->options['repo_lookup_method']], $args);
        } else {
            $alias     = $model->getRepository()->getTableAlias();
            $expr      = new ExpressionBuilder($this->connection);
            $composite = $expr->andX();

            $limit = 100;
            if ($data) {
                $composite->add(
                    $expr->in($alias.'.id', $data)
                );
                if (count($data) > $limit) {
                    $limit = $data;
                }
            }

            $choices = $model->getRepository()->getSimpleList($composite, [], $labelColumn, $idColumn, null, $limit);
        }

        return $choices;
    }

    /**
     * @param array $choices
     */
    protected function formatChoices(array &$choices)
    {
        // Get the first key
        reset($choices);
        $firstKey = key($choices);

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
