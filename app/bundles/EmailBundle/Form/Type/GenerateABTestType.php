<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Form\Type;

use Mautic\CoreBundle\Form\Type\FormButtonsType;
use Mautic\EmailBundle\Model\EmailModel;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Range;
use Symfony\Contracts\Translation\TranslatorInterface;

final class GenerateABTestType extends AbstractType
{
    public function __construct(private readonly EmailModel $emailModel, private readonly TranslatorInterface $translator)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $abTestWinnerCriteria = $this->emailModel->getBuilderComponents(null, 'abTestWinnerCriteria');

        $attr = [
            'class'    => 'form-control',
            'onchange' => 'Mautic.getAbTestWinnerForm(\'email\', \'emailform\', this);',
        ];

        if (!empty($abTestWinnerCriteria)) {
            $criteria    = $abTestWinnerCriteria['criteria'];
            $choices     = $abTestWinnerCriteria['choices'];
            $constraints = [];

            if ($options['is_parent']) {
                $constraints[] = new NotBlank(
                    ['message' => 'mautic.core.ab_test.winner_criteria.not_blank']
                );
            }

            $builder->add(
                'winnerCriteria',
                ChoiceType::class,
                [
                    'label'       => 'mautic.core.ab_test.form.winner',
                    'label_attr'  => ['class' => 'control-label'],
                    'attr'        => $attr,
                    'expanded'    => false,
                    'multiple'    => false,
                    'choices'     => $choices,
                    'placeholder' => 'mautic.core.form.chooseone',
                    'constraints' => $constraints,
                    'data'        => $options['data']['winnerCriteria'] ?? 'email.openrate',
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
                        if (!empty($criteria[$data['winnerCriteria']]['formTypeOptions'])) {
                            $formTypeOptions = array_merge($formTypeOptions, $criteria[$data['winnerCriteria']]['formTypeOptions']);
                        }
                        $form->add('properties', $criteria[$data['winnerCriteria']]['formType'], $formTypeOptions);
                    }
                }
            });
        }

        $builder->add('sendWinnerDelay', IntegerType::class, [
            'label'       => 'mautic.core.ab_test.form.send_winner_delay',
            'label_attr'  => ['class' => 'control-label'],
            'attr'        => $attr + ['postaddon_text' => $this->translator->trans('mautic.core.time.hours')],
            'constraints' => new Range([
                'min' => 1,
                'max' => 24,
            ]),
            'data'        => $options['data']['sendWinnerDelay'] ?? VariantType::DEFAULT_WINNER_DELAY,
        ]);

        $builder->add('totalWeight', IntegerType::class, [
            'label'       => 'mautic.core.ab_test.form.traffic_total_weight',
            'label_attr'  => ['class' => 'control-label'],
            'attr'        => $attr + ['postaddon_text' => '%'],
            'constraints' => new Range([
                'min' => 1,
                'max' => 50,
            ]),
            'data'        => $options['data']['totalWeight'] ?? VariantType::DEFAULT_WEIGHT,
        ]);

        $builder->add(
            'buttons',
            FormButtonsType::class,
            [
                'cancel_text'   => 'mautic.core.close',
                'save_text'     => false,
                'apply_text'    => 'mautic.core.form.saveandclose',
            ]
        );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'is_parent'         => true,
            'is_existing'       => false,
            'data_class'        => null,
        ]);
    }
}
