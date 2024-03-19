<?php

namespace Mautic\FormBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * @extends AbstractType<mixed>
 */
class PointActionFormSubmitType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('forms', FormListType::class, [
            'label'      => 'mautic.form.point.action.forms',
            'label_attr' => ['class' => 'control-label'],
            'required'   => false,
            'attr'       => [
                'class'   => 'form-control',
                'tooltip' => 'mautic.form.point.action.forms.descr',
            ],
        ]);
    }

    public function getBlockPrefix()
    {
        return 'pointaction_formsubmit';
    }
}
