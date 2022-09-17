<?php

/*
 * @copyright   2022 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\MessengerBundle\Form\Type;

use Mautic\CoreBundle\Form\EventListener\CleanFormSubscriber;
use Mautic\CoreBundle\Form\Type\SortableListType;
use Mautic\CoreBundle\Form\Type\StandAloneButtonType;
use Mautic\CoreBundle\Form\Type\YesNoButtonGroupType;
use Mautic\MessengerBundle\Model\MessengerType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Class ConfigType.
 */
class ConfigType extends AbstractType
{
    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var MessengerType
     */
    private MessengerType $messengerType;

    public function __construct(
        TranslatorInterface $translator,
        MessengerType $messengerType
    ) {
        $this->translator = $translator;
        $this->messengerType = $messengerType;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        /***
         * General fields that should show for all the transports
         */
        $messengerConditions = '{"config_messengerconfig_messenger_type":["async"]}';
        $messengerHideConditions = '{"config_messengerconfig_messenger_type":["sync"]}';

        $builder->add(
            'messenger_type',
            ChoiceType::class,
            [
                'choices'           => [
                    'mautic.messenger.config.enabled.true' => 'async',
                    'mautic.messenger.config.enabled.false'   => 'sync',
                ],
                'label'       => 'mautic.messenger.config.enabled',
                'label_attr'  => ['class' => 'control-label'],
                'required'    => false,
                'attr'        => [
                    'class'   => 'form-control',
                    'tooltip' => 'mautic.messenger.config.enabled.tooltip',
                ],
                'placeholder' => false,
            ]
        );

        $builder->add(
            'messenger_transport',
            ChoiceType::class,
            [
                'choices'           => $this->getMessengerTypeChoices(),
                'label'             => 'mautic.messenger.config.transport',
                'required'          => false,
                'attr'              => [
                    'class'        => 'form-control',
                    'data-show-on' => $messengerConditions,
                    'tooltip'      => 'mautic.messenger.config.transport.tooltip',
                    'onchange'     => 'Mautic.disableSendTestEmailButton()',
                ],
                'placeholder' => false,
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
     * @inheritdoc
     */
    public function getBlockPrefix()
    {
        return 'messengerconfig';
    }

    private function getMessengerTypeChoices(): array
    {
        $choices = [];
        $transports = $this->messengerType->getMessengerTypes();

        foreach ($transports as $value => $label) {
            $choices[$this->translator->trans($label)] = $value;
        }
        ksort($choices, SORT_NATURAL);

        return $choices;
    }
}
