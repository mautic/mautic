<?php

namespace Mautic\CoreBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

/**
 * Class SortableValueLabelListType.
 */
class SortableValueLabelListType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'label',
            TextType::class,
            [
                'label'          => 'mautic.core.label',
                'error_bubbling' => true,
                'attr'           => ['class' => 'form-control'],
            ]
        );

        $builder->add(
            'value',
            TextType::class,
            [
                'label'          => 'mautic.core.value',
                'error_bubbling' => true,
                'attr'           => ['class' => 'form-control'],
            ]
        );
    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        parent::buildView($view, $form, $options);

        $view->vars['preaddonAttr']  = (isset($options['attr']['preaddon_attr'])) ? $options['attr']['preaddon_attr'] : [];
        $view->vars['postaddonAttr'] = (isset($options['attr']['postaddon_attr'])) ? $options['attr']['postaddon_attr'] : [];
        $view->vars['preaddon']      = (isset($options['attr']['preaddon'])) ? $options['attr']['preaddon'] : [];
        $view->vars['postaddon']     = (isset($options['attr']['postaddon'])) ? $options['attr']['postaddon'] : [];
    }
}
