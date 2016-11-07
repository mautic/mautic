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
     */
    public function __construct(ModelFactory $modelFactory, TranslatorInterface $translator, Connection $connection)
    {
        $this->modelFactory = $modelFactory;
        $this->translator   = $translator;
        $this->connection   = $connection;
    }

    /**
     * @param $options
     */
    public function setOptions(Options $options)
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
     * @param $data
     * @param $options
     *
     * @return array
     */
    protected function getChoices($data = null, $includeNew = false)
    {
        if (null == $data) {
            $data = $this->selected;
        }

        $labelColumn = $this->options['entity_label_column'];
        $idColumn    = $this->options['entity_id_column'];
        $modelName   = $this->options['model'];
        $modalRoute  = $this->options['modal_route'];

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

                if (!$this->modelFactory->hasModel($modelName)) {
                    throw new \InvalidArgumentException("$modelName not found as a registered model service.");
                }
                $model = $this->modelFactory->getModel($modelName);
                if (!$model instanceof AjaxLookupModelInterface) {
                    throw new \InvalidArgumentException(get_class($model).' must implement '.AjaxLookupModelInterface::class);
                }

                $alias     = $model->getRepository()->getTableAlias();
                $expr      = new ExpressionBuilder($this->connection);
                $composite = $expr->andX();
                $composite->add(
                    $expr->in($alias.'.id', $data)
                );

                $validChoices = $model->getRepository()->getSimpleList($composite, [], $labelColumn, $idColumn);
                $choices      = [];
                foreach ($validChoices as $choice) {
                    $choices[$choice['value']] = $choice['label'];
                }

                $this->choices[$modelName] = $choices;
            }
        }

        $choices = ($includeNew && $modalRoute) ? array_replace(['new' => $this->translator->trans('mautic.core.createnew')], $this->choices[$modelName]) : $this->choices[$modelName];

        // must be [$label => $id]
        return array_flip($choices);
    }
}
