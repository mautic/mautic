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
    public function __construct(MauticFactory $factory)
    {
        $this->translator = $factory->getTranslator();
        $this->factory    = $factory;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventSubscriber(new CleanFormSubscriber(array('content' => 'html')));
        $builder->addEventSubscriber(new FormExitSubscriber('report.report', $options));

        // Only add these fields if we're in edit mode
        if (!$options['read_only']) {

            $builder->add('title', 'text', array(
                'label'      => 'mautic.report.report.form.title',
                'label_attr' => array('class' => 'control-label'),
                'attr'       => array('class' => 'form-control'),
                'required'   => true
            ));

            $builder->add('isPublished', 'button_group', array(
                'choice_list' => new ChoiceList(
                    array(false, true),
                    array('mautic.core.form.no', 'mautic.core.form.yes')
                ),
                'expanded'      => true,
                'multiple'      => false,
                'label'         => 'mautic.core.form.ispublished',
                'label_attr'    => array('class' => 'control-label'),
                'empty_value'   => false,
                'required'      => false
            ));

            $builder->add('system', 'button_group', array(
                'choice_list' => new ChoiceList(
                    array(false, true),
                    array('mautic.core.form.no', 'mautic.core.form.yes')
                ),
                'expanded'      => true,
                'multiple'      => false,
                'label'         => 'mautic.report.report.form.issystem',
                'label_attr'    => array('class' => 'control-label'),
                'empty_value'   => false,
                'required'      => false
            ));

            // Quickly build the table source list for use in the selector
            $tables = $this->buildTableSourceList($options['table_list']);

            // Build a list of data sources
            $builder->add('source', 'choice', array(
                'choices'       => $tables,
                'expanded'      => false,
                'multiple'      => false,
                'label'         => 'mautic.report.report.form.source',
                'label_attr'    => array('class' => 'control-label'),
                'empty_value'   => false,
                'required'      => false,
                'attr'          => array(
                    'class'    => 'form-control',
                    'tooltip'  => 'mautic.report.report.form.source.help',
                    'onchange' => 'Mautic.updateColumnList()'
                )
            ));

            $source     = (!is_null($options['data']->getSource()) && $options['data']->getSource() != '') ? $options['data']->getSource() : key($tables);
            $columns    = $options['table_list'][$source]['columns'];
            $columnList = $this->buildColumnSelectList($columns);

            $formModifier = function (FormInterface $form, $source) {
                $model      = $this->factory->getModel('report');
                $tableData  = $model->getTableData();
                $columns    = $tableData[$source]['columns'];
                $columnList = $this->buildColumnSelectList($columns);

                $form->add('columns', 'choice', array(
                    'choices'    => $columnList,
                    'label'      => 'mautic.report.report.form.columnselector',
                    'label_attr' => array('class' => 'control-label'),
                    'required'   => false,
                    'multiple'   => true,
                    'expanded'   => false,
                    'attr'       => array(
                        'class' => 'form-control'
                    )
                ));
            };

            $builder->addEventListener(
                FormEvents::PRE_SET_DATA,
                function (FormEvent $event) use ($formModifier) {
                    $formModifier($event->getForm(), $event->getData()->getSource());
                }
            );

            $builder->get('source')->addEventListener(
                FormEvents::POST_SUBMIT,
                function (FormEvent $event) use ($formModifier) {
                    // since we've added the listener to the child, we'll have to pass on
                    // the parent to the callback functions!
                    $formModifier($event->getForm()->getParent(), $event->getForm()->getData());
                }
            );

            // Build the filter selector
            $builder->add('filters', 'collection', array(
                'type'         => 'filter_selector',
                'label'        => 'mautic.report.report.form.filterselector',
                'label_attr'   => array('class' => 'control-label'),
                'options'      => array(
                    'columnList' => $columnList,
                    'required'   => false
                ),
                'allow_add'    => true,
                'allow_delete' => true,
                'prototype'    => true,
                'required'     => false
            ));

            $builder->add('buttons', 'form_buttons');
        }

        if (!empty($options["action"])) {
            $builder->setAction($options["action"]);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Mautic\ReportBundle\Entity\Report',
            'table_list' => array()
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return "report";
    }

    /**
     * Builds an array for the column selectors
     *
     * @param array $columns Array with the column list
     *
     * @return array
     */
    private function buildColumnSelectList($columns)
    {
        // Create an array of columns, the key is the column value stored in the database and the value is what the user sees
        $list = array();
        foreach ($columns as $column => $data) {
            if (isset($data['label'])) {
                $list[$column] = $data['label'];
            }
        }

        return $list;
    }

    /**
     * Extracts the keys from the table_list option and builds an array of tables for the select list
     *
     * @param array $tables Array with the table list and columns
     *
     * @return array
     */
    private function buildTableSourceList($tables)
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
