<?php

namespace Mautic\SmsBundle\Form\Type;

use Mautic\SmsBundle\Sms\TransportChain;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Class ConfigType.
 */
class ConfigType extends AbstractType
{
    /**
     * @var TransportChain
     */
    private $transportChain;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * ConfigType constructor.
     */
    public function __construct(TransportChain $transportChain, TranslatorInterface $translator)
    {
        $this->transportChain = $transportChain;
        $this->translator     = $translator;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
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
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'smsconfig';
    }
}
