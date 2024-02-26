<?php

namespace Mautic\ReportBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @extends AbstractType<array<mixed>>
 */
class TableOrderType extends AbstractType
{
    public function __construct(
        private TranslatorInterface $translator
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
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

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $view->vars = array_replace($view->vars, [
            'columnList' => $options['columnList'],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'columnList' => [],
        ]);
    }
}
