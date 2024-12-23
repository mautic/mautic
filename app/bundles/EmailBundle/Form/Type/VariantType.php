<?php

namespace Mautic\EmailBundle\Form\Type;

use Mautic\EmailBundle\Model\EmailModel;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * @extends AbstractType<mixed>
 */
class VariantType extends AbstractType
{
    public function __construct(
        private EmailModel $emailModel,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('weight',
            IntegerType::class,
            [
                'label'      => 'mautic.core.ab_test.form.traffic_weight',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'   => 'form-control',
                    'tooltip' => 'mautic.core.ab_test.form.traffic_weight.help',
                ],
                'constraints' => [
                    new NotBlank(
                        ['message' => 'mautic.email.variant.weight.notblank']
                    ),
                ],
            ]
        );

        $abTestWinnerCriteria = $this->emailModel->getBuilderComponents(null, 'abTestWinnerCriteria');

        if (!empty($abTestWinnerCriteria)) {
            $criteria = $abTestWinnerCriteria['criteria'];
            $choices  = $abTestWinnerCriteria['choices'];

            $builder->add(
                'winnerCriteria',
                ChoiceType::class, [
                    'label'             => 'mautic.core.ab_test.form.winner',
                    'label_attr'        => ['class' => 'control-label'],
                    'attr'              => [
                        'class'    => 'form-control',
                        'onchange' => 'Mautic.getAbTestWinnerForm(\'email\', \'emailform\', this);',
                    ],
                    'expanded'    => false,
                    'multiple'    => false,
                    'choices'     => $choices,
                    'placeholder' => 'mautic.core.form.chooseone',
                    'constraints' => [
                        new NotBlank(
                            ['message' => 'mautic.core.ab_test.winner_criteria.not_blank']
                        ),
                    ],
                ]
            );

            $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) use ($criteria): void {
                $form = $event->getForm();
                $data = $event->getData();

                if (isset($data['winnerCriteria'])) {
                    if (!empty($criteria[$data['winnerCriteria']]['formType'])) {
                        $formTypeOptions = [
                            'required' => false,
                            'label'    => false,
                        ];
                        if (!empty($criteria[$data]['formTypeOptions'])) {
                            $formTypeOptions = array_merge($formTypeOptions, $criteria[$data]['formTypeOptions']);
                        }
                        $form->add('properties', $criteria[$data]['formType'], $formTypeOptions);
                    }
                }
            });
        }
    }

    public function getBlockPrefix(): string
    {
        return 'emailvariant';
    }
}
