<?php

namespace Mautic\EmailBundle\Form\Type;

use Mautic\CoreBundle\Form\Type\YesNoButtonGroupType;
use Mautic\EmailBundle\Model\EmailModel;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraints\NotBlank;

class VariantType extends AbstractType
{
    /**
     * @var EmailModel
     */
    private $emailModel;

    public function __construct(EmailModel $emailModel)
    {
        $this->emailModel = $emailModel;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'enableAbTest',
            YesNoButtonGroupType::class,
            [
                'label' => 'mautic.core.ab_test.form.enable',
                'attr'  => [
                    'tooltip' => 'mautic.core.ab_test.form.enable.help',
                    'class'   => 'form-control',
                ],
            ]
        );

        $builder->add('weight', IntegerType::class, [
            'label'      => 'mautic.core.ab_test.form.traffic_weight',
            'label_attr' => ['class' => 'control-label'],
            'attr'       => [
                'class'    => 'form-control',
                'tooltip'  => 'mautic.core.ab_test.form.traffic_weight.help',
            ],
        ]);

        $attr = [
            'class'    => 'form-control',
            'tooltip'  => 'mautic.core.ab_test.form.traffic_weight_total.help',
        ];

        if ($options['is_parent'] === true && $options['is_existing'] === false) {
            $attr['data-show-on'] = '{"emailform_variantSettings_enableAbTest_1":"checked"}';
        }

        $builder->add('weightTotal', IntegerType::class, [
            'label'      => 'mautic.core.ab_test.form.traffic_weight_total',
            'label_attr' => ['class' => 'control-label'],
            'attr'       => $attr,
            'constraints' => new Assert\Range([
                'min' => 0,
                'max' => 100,
            ]),
        ]);

        $abTestWinnerCriteria = $this->emailModel->getBuilderComponents(null, 'abTestWinnerCriteria');

        if (!empty($abTestWinnerCriteria)) {
            $criteria = $abTestWinnerCriteria['criteria'];
            $choices  = $abTestWinnerCriteria['choices'];

            $attr = [
                'class'        => 'form-control',
                'onchange'     => 'Mautic.getAbTestWinnerForm(\'email\', \'emailform\', this);',
                'disabled'     => !$options['is_parent'],
            ];

            if ($options['is_parent'] === true && $options['is_existing'] === false) {
                $attr['data-show-on'] = '{"emailform_variantSettings_enableAbTest_1":"checked"}';
            }

            $builder->add('winnerCriteria', ChoiceType::class, [
                'label'      => 'mautic.core.ab_test.form.winner',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => $attr,
                'expanded'    => false,
                'multiple'    => false,
                'choices'     => $choices,
                'empty_value' => 'mautic.core.form.chooseone',
                'constraints' => !$options['is_parent'] ? null : [
                    new NotBlank(
                        ['message' => 'mautic.core.ab_test.winner_criteria.not_blank', 'groups' => 'variant']
                    ),
                ],
            ]);

            $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) use ($criteria) {
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

    /**
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'emailvariant';
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults([
            'is_parent'         => true,
            'is_existing'       => false,
            'validation_groups' => function (Form $form) {
                $data = $form->getData();

                $groups = ['Default'];

                if ($data['enableAbTest']) {
                    $groups[] = 'variant';
                }

                return $groups;
            },
        ]);
    }
}
