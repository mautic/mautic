<?php

namespace Mautic\PageBundle\Form\Type;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RedirectListType extends AbstractType
{
    public function __construct(
        private CoreParametersHelper $coreParametersHelper
    ) {
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $choices = $this->coreParametersHelper->get('redirect_list_types');
        $choices = (null === $choices) ? [] : array_flip($choices);

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
