<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

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

    public function __construct(TransportChain $transportChain, TranslatorInterface $translator)
    {
        $this->transportChain = $transportChain;
        $this->translator     = $translator;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $choices    = [];
        $transports = $this->transportChain->getEnabledTransports();
        foreach ($transports as $transportServiceId=>$transport) {
            $choices[$transportServiceId] = $this->translator->trans($transportServiceId);
        }

        $builder->add('sms_transport', ChoiceType::class, [
            'label'      => 'mautic.sms.config.select_default_transport',
            'label_attr' => ['class' => 'control-label'],
            'attr'       => [
                'class'   => 'form-control',
                'tooltip' => 'mautic.sms.config.select_default_transport',
            ],
            'choices'   => $choices,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'smsconfig';
    }
}
