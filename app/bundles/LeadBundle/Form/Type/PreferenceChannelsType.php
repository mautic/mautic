<?php

namespace Mautic\LeadBundle\Form\Type;

use Mautic\LeadBundle\Model\LeadModel;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PreferenceChannelsType extends AbstractType
{
    public function __construct(
        private LeadModel $leadModel
    ) {
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $model = $this->leadModel;

        $resolver->setDefaults(
            [
                'choices'     => fn (Options $options) => $model->getPreferenceChannels(),
                'placeholder' => '',
                'attr'        => ['class' => 'form-control'],
                'label_attr'  => ['class' => 'control-label'],
                'multiple'    => false,
                'expanded'    => false,
                'required'    => false,
            ]
        );
    }

    public function getParent()
    {
        return ChoiceType::class;
    }
}
