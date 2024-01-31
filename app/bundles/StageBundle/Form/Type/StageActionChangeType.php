<?php

namespace Mautic\StageBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * @extends AbstractType<array<mixed>>
 */
class StageActionChangeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('stage', StageActionListType::class, [
            'label'      => 'mautic.stage.selectstage',
            'label_attr' => ['class' => 'control-label'],
            'attr'       => [
                'class'   => 'form-control',
                'tooltip' => 'mautic.stage.choose.stage_descr',
            ],
            'multiple'    => false,
            'required'    => true,
            'constraints' => [
                new NotBlank(
                    ['message' => 'mautic.core.value.required']
                ),
            ],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefined(['update_select']);
    }

    /**
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'stageaction_change';
    }
}
