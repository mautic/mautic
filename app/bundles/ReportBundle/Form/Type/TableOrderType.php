<?php

namespace Mautic\ReportBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatorInterface;

class TableOrderType extends AbstractType
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
                    'class' => 'form-control',
                ],
            ]
        );

        // Direction
        $builder->add(
            'direction',
            ChoiceType::class,
            [
                'choices'           => [
                    $this->translator->trans('mautic.report.report.label.tableorder_dir.asc')  => 'ASC',
                    $this->translator->trans('mautic.report.report.label.tableorder_dir.desc') => 'DESC',
                ],
                'expanded'    => false,
                'multiple'    => false,
                'label'       => 'mautic.core.order',
                'label_attr'  => ['class' => 'control-label'],
                'placeholder' => false,
                'required'    => false,
                'attr'        => [
                    'class' => 'form-control not-chosen',
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
        return 'table_order';
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
