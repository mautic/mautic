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
                'label'      => 'mautic.report.report.form.name',
                'label_attr' => array('class' => 'control-label'),
                'attr'       => array('class' => 'form-control'),
                'required'   => true
            ));

            $builder->add('description', 'textarea', array(
                'label'      => 'mautic.report.report.form.description',
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
                    'onchange' => 'Mautic.updateColumnList(this.value)'
                )
            ));

            /** @var \Mautic\ReportBundle\Model\ReportModel $model */
            $model   = $this->factory->getModel('report');
            $report  = $options['data'];
            $formModifier = function (FormInterface $form, $source = '') use ($model, $report) {
                list($columnList, $types) = $model->getColumnList($source);
                $currentColumns = $report->getColumns();
                if (is_array($currentColumns)) {
                    $orderColumns = array_values($currentColumns);
                    $order        = htmlspecialchars(json_encode($orderColumns), ENT_QUOTES, 'UTF-8');
                } else {
                    $order = '[]';
                }

                // Build the columns selector
                $form->add('columns', 'choice', array(
                    'choices'    => $columnList,
                    'label'      => 'mautic.report.report.form.columnselector',
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
                $form->add('filters', 'collection', array(
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
                    'required'     => false,
                    'attr'         => array(
                        'data-column-types' => $types
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