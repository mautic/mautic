<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
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
        $builder->add('label', 'text', ['label' => 'mautic.core.label', 'error_bubbling' => true, 'attr' => ['class' => 'form-control']]);
        $builder->add('value', 'text', ['label' => 'mautic.core.value', 'error_bubbling' => true, 'attr' => ['class' => 'form-control']]);
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
