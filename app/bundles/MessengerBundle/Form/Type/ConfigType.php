<?php

declare(strict_types=1);

namespace Mautic\MessengerBundle\Form\Type;

use Mautic\EmailBundle\Form\DataTransformer\DsnTransformer;
use Mautic\EmailBundle\Form\Type\DsnType;
use Mautic\MessengerBundle\Validator\Dsn;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;

class ConfigType extends AbstractType
{
    public function __construct(private DsnTransformer $dsnTransformer)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add(
            'messenger_dsn',
            DsnType::class,
            [
                'label'       => false,
                'constraints' => [
                    new Dsn(),
                ],
                'error_mapping' => [
                    '.' => 'scheme',
                ],
            ]
        );

        $builder->get('messenger_dsn')
            ->addModelTransformer($this->dsnTransformer);

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
            ]
        );

        $builder->add(
            'messenger_retry_strategy_multiplier',
            NumberType::class,
            [
                'scale'      => 0,
                'label'      => 'mautic.messenger.config.retry_strategy.multiplier',
                'label_attr' => ['class' => 'control-label'],
                'required'   => false,
                'attr'       => [
                    'class' => 'form-control',
                ],
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
            ]
        );
        /***
         * For doctorine we are using the default settings
         * for other transports their settings should be injected here
         * Here is an example of the fields that needs to be added
         * https://symfony.com/doc/current/messenger.html#doctrine-transport
         */
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'messengerconfig';
    }
}
