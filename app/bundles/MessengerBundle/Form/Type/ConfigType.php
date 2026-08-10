<?php

declare(strict_types=1);

namespace Mautic\MessengerBundle\Form\Type;

use Mautic\ConfigBundle\Form\Type\DsnType;
use Mautic\MessengerBundle\Validator\Dsn;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;
use Symfony\Contracts\Translation\TranslatorInterface;

class ConfigType extends AbstractType
{
    public function __construct(
        private TranslatorInterface $translator,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $testButton = [
            'action' => 'messenger:sendTestMessage',
            'label'  => $this->translator->trans('mautic.messenger.config.dsn.send_test_message'),
        ];

        $builder->add(
            'messenger_dsn_email',
            DsnType::class,
            [
                'constraints' => [new Dsn()],
                'test_button' => $testButton,
            ]
        );

        $builder->add(
            'messenger_dsn_hit',
            DsnType::class,
            [
                'constraints' => [new Dsn()],
                'test_button' => $testButton,
            ]
        );

        $builder->add(
            'messenger_dsn_failed',
            DsnType::class,
            [
                'constraints' => [new Dsn()],
                'required'    => false,
                'test_button' => $testButton,
            ]
        );

        $builder->add(
            'messenger_retry_strategy_max_retries',
            NumberType::class,
            [
                'label'      => 'mautic.messenger.config.retry_strategy.max_retries',
                'label_attr' => ['class' => 'control-label'],
                'required'   => false,
                'attr'       => [
                    'class' => 'form-control',
                ],
                'constraints' => $this->greaterThanOrEqualConstraint(0),
            ]
        );

        $builder->add(
            'messenger_retry_strategy_delay',
            NumberType::class,
            [
                'label'      => 'mautic.messenger.config.retry_strategy.delay',
                'label_attr' => ['class' => 'control-label'],
                'required'   => false,
                'attr'       => [
                    'class' => 'form-control',
                ],
                'constraints' => $this->greaterThanOrEqualConstraint(0),
            ]
        );

        $builder->add(
            'messenger_retry_strategy_multiplier',
            NumberType::class,
            [
                'scale'      => 2,
                'label'      => 'mautic.messenger.config.retry_strategy.multiplier',
                'label_attr' => ['class' => 'control-label'],
                'required'   => false,
                'attr'       => [
                    'class' => 'form-control',
                ],
                // Symfony's MultiplierRetryStrategy throws
                // InvalidArgumentException for any multiplier < 1, which would
                // otherwise crash the queue worker after the form is saved.
                'constraints' => $this->greaterThanOrEqualConstraint(1),
            ]
        );

        $builder->add(
            'messenger_retry_strategy_max_delay',
            NumberType::class,
            [
                'label'      => 'mautic.messenger.config.retry_strategy.max_delay',
                'label_attr' => ['class' => 'control-label'],
                'required'   => false,
                'attr'       => [
                    'class' => 'form-control',
                ],
                'constraints' => $this->greaterThanOrEqualConstraint(0),
            ]
        );
    }

    /**
     * @return list<GreaterThanOrEqual>
     */
    private function greaterThanOrEqualConstraint(int|float $value): array
    {
        return [new GreaterThanOrEqual(['value' => $value])];
    }

    public function getBlockPrefix(): string
    {
        return 'messengerconfig';
    }
}
