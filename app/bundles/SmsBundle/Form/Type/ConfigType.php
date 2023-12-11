<?php

namespace Mautic\SmsBundle\Form\Type;

use Mautic\CoreBundle\Form\Type\YesNoButtonGroupType;
use Mautic\SmsBundle\Sms\TransportChain;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class ConfigType extends AbstractType
{
    public const SMS_DISABLE_TRACKABLE_URLS = 'sms_disable_trackable_urls';

    public function __construct(
        private TransportChain $transportChain,
        private TranslatorInterface $translator
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $choices    = [];
        $transports = $this->transportChain->getEnabledTransports();
        foreach ($transports as $transportServiceId=>$transport) {
            $choices[$this->translator->trans($transportServiceId)] = $transportServiceId;
        }

        $builder->add('sms_transport', ChoiceType::class, [
            'label'      => 'mautic.sms.config.select_default_transport',
            'label_attr' => ['class' => 'control-label'],
            'attr'       => [
                'class'   => 'form-control',
                'tooltip' => 'mautic.sms.config.select_default_transport',
            ],
            'choices'           => $choices,
            ]);

        $builder->add(
            self::SMS_DISABLE_TRACKABLE_URLS,
            YesNoButtonGroupType::class,
            [
                'label' => 'mautic.sms.config.form.sms.disable_trackable_urls',
                'attr'  => [
                    'tooltip' => 'mautic.sms.config.form.sms.disable_trackable_urls.tooltip',
                ],
                'data'=> !empty($options['data'][self::SMS_DISABLE_TRACKABLE_URLS]) ? true : false,
            ]
        );
    }

    public function getBlockPrefix()
    {
        return 'smsconfig';
    }
}
