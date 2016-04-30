<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ReportBundle\Form\Type;

use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\CoreBundle\Form\EventListener\CleanFormSubscriber;
use Mautic\CoreBundle\Form\EventListener\FormExitSubscriber;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\ChoiceList\ChoiceList;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormInterface;
use Mautic\ReportBundle\Entity\Report;

/**
 * Class ReportType
 */
class ReportType extends AbstractType
{
    /**
     * Factory object
     *
     * @var \Mautic\CoreBundle\Factory\MauticFactory
     */
    private $factory;

    /**
     * Translator object
     *
     * @var \Symfony\Bundle\FrameworkBundle\Translation\Translator
     */
    private $translator;

    /**
     * @param MauticFactory $factory
     */
    public function __construct (MauticFactory $factory)
    {
        $this->translator = $factory->getTranslator();
        $this->factory    = $factory;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm (FormBuilderInterface $builder, array $options)
    {
        $builder->addEventSubscriber(new CleanFormSubscriber(array('description' => 'html')));
        $builder->addEventSubscriber(new FormExitSubscriber('report', $options));

        // Only add these fields if we're in edit mode
        if (!$options['read_only']) {

            $builder->add('name', 'text', array(
                'label'      => 'mautic.core.name',
                'label_attr' => array('class' => 'control-label'),
                'attr'       => array('class' => 'form-control'),
                'required'   => true
            ));

            $builder->add('description', 'textarea', array(
                'label'      => 'mautic.core.description',
                'label_attr' => array('class' => 'control-label'),
                'attr'       => array('class' => 'form-control editor'),
                'required'   => false
            ));

            $builder->add('isPublished', 'yesno_button_group');

            $data = $options['data']->getSystem();
            $builder->add('system', 'yesno_button_group', array(
                'label' => 'mautic.report.report.form.issystem',
                'data'  => $data,
                'attr'  => array(
                    'tooltip' => 'mautic.report.report.form.issystem.tooltip'
                )
            ));

            // Quickly build the table source list for use in the selector
            $tables = $this->buildTableSourceList($options['table_list']);

            // Build a list of data sources
            $builder->add('source', 'choice', array(
                'choices'     => $tables,
                'expanded'    => false,
                'multiple'    => false,
                'label'       => 'mautic.report.report.form.source',
                'label_attr'  => array('class' => 'control-label'),
                'empty_value' => false,
                'required'    => false,
                'attr'        => array(
                    'class'    => 'form-control',
                    'tooltip'  => 'mautic.report.report.form.source.help',
                    'onchange' => 'Mautic.updateReportSourceData(this.value)'
                )
            ));

            /** @var \Mautic\ReportBundle\Model\ReportModel $model */
            $model     = $this->factory->getModel('report');
            $tableList = $options['table_list'];
            $formModifier = function (FormInterface $form, $source, $currentColumns, $currentGraphs, $formData) use ($model, $tables, $tableList) {
                if(empty($source)) {
                    reset($tables);
                    $first_key = key($tables);
                    $source    = $first_key;
                }

                list($columnList, $types) = $model->getColumnList($source);
                if (is_array($currentColumns)) {
                    $orderColumns = array_values($currentColumns);
                    $order        = htmlspecialchars(json_encode($orderColumns), ENT_QUOTES, 'UTF-8');
                } else {
                    $order = '[]';
                }

                // Build the columns selector
                $form->add('columns', 'choice', array(
                    'choices'    => $columnList,
                    'label'      => false,
                    'label_attr' => array('class' => 'control-label'),
                    'required'   => false,
                    'multiple'   => true,
                    'expanded'   => false,
                    'attr'       => array(
                        'class'         => 'form-control multiselect',
                        'data-order'    => $order,
                        'data-sortable' => 'true'
                    )
                ));

                // Build the filter selector
                $form->add('filters', 'report_filters', array(
                    'type'         => 'filter_selector',
                    'label'        => false,
                    'options'      => array(
                        'columnList' => $columnList,
                        'required'   => false
                    ),
                    'allow_add'    => true,
                    'allow_delete' => true,
                    'prototype'    => true,
                    'required'     => false,
                    'attr'         => array(
                        'data-column-types' => $types
                    ),
                    'columns'      => $tableList[$source]['columns'],
                    'report'       => $formData
                ));

                $form->add('tableOrder', 'collection', array(
                    'type'         => 'table_order',
                    'label'        => false,
                    'options'      => array(
                        'columnList' => $columnList,
                        'required'   => false
                    ),
                    'allow_add'    => true,
                    'allow_delete' => true,
                    'prototype'    => true,
                    'required'     => false
                ));

                // Templates for values
                $form->add('value_template_yesno', 'yesno_button_group', array(
                    'label'  => false,
                    'mapped' => false,
                    'attr'   => array(
                        'class' => 'filter-value'
                    ),
                    'data'   => 1,
                    'choice_list' => new ChoiceList(
                        array(0, 1),
                        array('mautic.core.form.no', 'mautic.core.form.yes')
                    ),
                ));


                $graphList = $model->getGraphList($source);
                if (is_array($currentGraphs)) {
                    $orderColumns = array_values($currentGraphs);
                    $order        = htmlspecialchars(json_encode($orderColumns), ENT_QUOTES, 'UTF-8');
                } else {
                    $order = '[]';
                }

                $form->add('graphs', 'choice', array(
                    'choices'    => $graphList,
                    'label'      => 'mautic.report.report.form.graphs',
                    'label_attr' => array('class' => 'control-label'),
                    'required'   => false,
                    'multiple'   => true,
                    'expanded'   => false,
                    'attr'       => array(
                        'class'         => 'form-control multiselect',
                        'data-order'    => $order,
                        'data-sortable' => 'true'
                    )
                ));
            };

            $builder->addEventListener(FormEvents::PRE_SET_DATA,
                function (FormEvent $event) use ($formModifier) {
                    $data = $event->getData();
                    $formModifier($event->getForm(), $data->getSource(), $data->getColumns(), $data->getGraphs(), $data);
                }
            );

            $builder->addEventListener(FormEvents::PRE_SUBMIT,
                function (FormEvent $event) use ($formModifier) {
                    $data    = $event->getData();
                    $graphs  = (isset($data['graphs'])) ? $data['graphs'] : array();
                    $columns = (isset($data['columns'])) ? $data['columns'] : array();
                    $formModifier($event->getForm(), $data['source'], $columns, $graphs, $data);
                }
            );

            $builder->add('buttons', 'form_buttons');
        }

        if (!empty($options["action"])) {
            $builder->setAction($options["action"]);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions (OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Mautic\ReportBundle\Entity\Report',
            'table_list' => array()
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getName ()
    {
        return "report";
    }

    /**
     * Extracts the keys from the table_list option and builds an array of tables for the select list
     *
     * @param array $tables Array with the table list and columns
     *
     * @return array
     */
    private function buildTableSourceList ($tables)
    {
        $temp = array_keys($tables);

        // Create an array of tables, the key is the value stored in the database and the value is what the user sees
        $list = array();

        foreach ($temp as $table) {
            $list[$table] = $tables[$table]['display_name'];
        }

        return $list;
    }
}