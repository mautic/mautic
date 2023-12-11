<?php

namespace Mautic\StageBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;

class GenericStageActionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $default = (empty($options['data']['weight'])) ? 0 : (int) $options['data']['weight'];
        $builder->add('weight', NumberType::class, [
            'label'      => 'mautic.stage.action.weight',
            'label_attr' => ['class' => 'control-label'],
            'attr'       => [
                    'class'   => 'form-control',
                    'tooltip' => 'mautic.stage.action.weight.help',
                ],
            'scale' => 0,
            'data'  => $default,
        ]);
    }

    public function getBlockPrefix()
    {
        return 'genericstage_settings';
    }
}
