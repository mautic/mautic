<?php

namespace Mautic\StageBundle\Form\Type;

use Mautic\CoreBundle\Form\Type\FormButtonsType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Choice;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * @extends AbstractType<mixed>
 */
class StageMergeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add(
            'stage_to_merge',
            ChoiceType::class,
            [
                'choices'     => $options['stages'],
                'multiple'    => false,
                'label'       => 'mautic.stage.to.merge.into',
                'required'    => true,
                'placeholder' => 'mautic.core.form.chooseone',
                'constraints' => [
                    new NotBlank(
                        ['message' => 'mautic.core.value.required']
                    ),
                    new Choice([
                        'choices' => array_values($options['stages']),
                        'message' => 'mautic.core.value.invalid',
                    ]),
                ],
            ]
        );
        $builder->add(
            'buttons',
            FormButtonsType::class,
            [
                'apply_text' => false,
                'save_text'  => 'mautic.lead.merge',
                'save_icon'  => 'ri-flag-line',
            ]
        );

        if (!empty($options['action'])) {
            $builder->setAction($options['action']);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired(['stages']);
    }
}
