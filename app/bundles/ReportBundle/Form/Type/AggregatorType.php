<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ReportBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Class AggregatorType.
 */
class AggregatorType extends AbstractType
{
    private $translator;

    public function __construct($translator)
    {
        $this->translator = $translator;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        // function
        $builder->add('function', 'choice', [
            'choices' => [
                'COUNT' => $this->translator->trans('mautic.report.report.label.aggregators.count'),
                'AVG'   => $this->translator->trans('mautic.report.report.label.aggregators.avg'),
                'SUM'   => $this->translator->trans('mautic.report.report.label.aggregators.sum'),
                'MIN'   => $this->translator->trans('mautic.report.report.label.aggregators.min'),
                'MAX'   => $this->translator->trans('mautic.report.report.label.aggregators.max'),
            ],
            'expanded'    => false,
            'multiple'    => false,
            'label'       => 'mautic.report.function',
            'label_attr'  => ['class' => 'control-label'],
            'empty_value' => false,
            'required'    => false,
            'attr'        => [
                'class' => 'form-control not-chosen',
            ],
        ]);

        // Build a list of columns
        $builder->add('column', 'choice', [
            'choices'     => $options['columnList'],
            'expanded'    => false,
            'multiple'    => false,
            'label'       => 'mautic.report.report.label.filtercolumn',
            'label_attr'  => ['class' => 'control-label'],
            'empty_value' => false,
            'required'    => false,
            'attr'        => [
                'class' => 'form-control filter-columns',
            ],
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars = array_replace($view->vars, [
            'columnList' => $options['columnList'],
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'aggregator';
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults([
            'columnList' => [],
        ]);
    }
}
