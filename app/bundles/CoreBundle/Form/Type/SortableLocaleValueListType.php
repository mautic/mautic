<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\LocaleType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

/**
 * @extends AbstractType<mixed>
 */
final class SortableLocaleValueListType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add(
            'label',
            LocaleType::class,
            [
                'label'          => 'mautic.core.language',
                'placeholder'    => 'mautic.core.form.chooseone',
                'error_bubbling' => true,
                'attr'           => ['class' => 'form-control not-chosen'],
            ]
        );

        $builder->add(
            'value',
            TextareaType::class,
            [
                'label'          => 'mautic.core.value',
                'error_bubbling' => true,
                'required'       => false,
                'attr'           => ['class' => 'form-control', 'rows' => 1],
            ]
        );
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $view->vars['preaddonAttr']  = $options['attr']['preaddon_attr'] ?? [];
        $view->vars['postaddonAttr'] = $options['attr']['postaddon_attr'] ?? [];
        $view->vars['preaddon']      = $options['attr']['preaddon'] ?? [];
        $view->vars['postaddon']     = $options['attr']['postaddon'] ?? [];
    }

    public function getBlockPrefix(): string
    {
        return 'sortable_value_label_list';
    }
}
