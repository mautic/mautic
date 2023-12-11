<?php

namespace Mautic\PageBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class PointActionPageHitType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('pages', PageListType::class, [
            'label'      => 'mautic.page.point.action.form.pages',
            'label_attr' => ['class' => 'control-label'],
            'attr'       => [
                'class'   => 'form-control',
                'tooltip' => 'mautic.page.point.action.form.pages.descr',
            ],
        ]);
    }

    public function getBlockPrefix()
    {
        return 'pointaction_pagehit';
    }
}
