<?php

namespace Mautic\PageBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<array<mixed>>
 */
class RedirectListType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $choices = [
            'mautic.page.form.redirecttype.permanent'     => 301,
            'mautic.page.form.redirecttype.temporary'     => 302,
            'mautic.page.form.redirecttype.303_temporary' => 303,
            'mautic.page.form.redirecttype.307_temporary' => 307,
            'mautic.page.form.redirecttype.308_permanent' => 308,
        ];

        $resolver->setDefaults([
            'choices'     => $choices,
            'expanded'    => false,
            'multiple'    => false,
            'label'       => 'mautic.page.form.redirecttype',
            'label_attr'  => ['class' => 'control-label'],
            'placeholder' => false,
            'required'    => false,
            'attr'        => [
                'class' => 'form-control',
            ],
            'feature'           => 'all',
        ]);

        $resolver->setDefined(['feature']);
    }

    /**
     * @return string
     */
    public function getParent()
    {
        return ChoiceType::class;
    }
}
