<?php

namespace Mautic\ReportBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatorInterface;

class AggregatorType extends AbstractType
{
    /**
     * @var TranslatorInterface
     */
    private $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'function',
            ChoiceType::class,
            [
                'choices'           => [
                    $this->translator->trans('mautic.report.report.label.aggregators.count') => 'COUNT',
                    $this->translator->trans('mautic.report.report.label.aggregators.avg')   => 'AVG',
                    $this->translator->trans('mautic.report.report.label.aggregators.sum')   => 'SUM',
                    $this->translator->trans('mautic.report.report.label.aggregators.min')   => 'MIN',
                    $this->translator->trans('mautic.report.report.label.aggregators.max')   => 'MAX',
                ],
                'expanded'    => false,
                'multiple'    => false,
                'label'       => 'mautic.report.function',
                'label_attr'  => ['class' => 'control-label'],
                'placeholder' => false,
                'required'    => false,
                'attr'        => [
                    'class' => 'form-control not-chosen',
                ],
            ]
        );

        // Build a list of columns
        $builder->add(
            'column',
            ChoiceType::class,
            [
                'choices'           => array_flip($options['columnList']),
                'expanded'          => false,
                'multiple'          => false,
                'label'             => 'mautic.report.report.label.filtercolumn',
                'label_attr'        => ['class' => 'control-label'],
                'placeholder'       => false,
                'required'          => false,
                'attr'              => [
                    'class' => 'form-control filter-columns',
                ],
            ]
        );
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
    public function getBlockPrefix()
    {
        return 'aggregator';
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'columnList' => [],
        ]);
    }
}
