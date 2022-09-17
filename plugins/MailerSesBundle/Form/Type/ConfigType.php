<?php

namespace MauticPlugin\MailerSesBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Class ConfigType.
 */
class ConfigType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'mailer_option_region',
            ChoiceType::class,
            [
                'choices'           => [
                    'mautic.email.config.mailer.amazon_region.us_east_1'      => 'us-east-1',
                    'mautic.email.config.mailer.amazon_region.us_east_2'      => 'us-east-2',
                    'mautic.email.config.mailer.amazon_region.us_west_2'      => 'us-west-2',
                    'mautic.email.config.mailer.amazon_region.ap_south_1'     => 'ap-south-1',
                    'mautic.email.config.mailer.amazon_region.ap_northeast_2' => 'ap-northeast-2',
                    'mautic.email.config.mailer.amazon_region.ap_southeast_1' => 'ap-southeast-1',
                    'mautic.email.config.mailer.amazon_region.ap_southeast_2' => 'ap-southeast-2',
                    'mautic.email.config.mailer.amazon_region.ap_northeast_1' => 'ap-northeast-1',
                    'mautic.email.config.mailer.amazon_region.ca_central_1'   => 'ca-central-1',
                    'mautic.email.config.mailer.amazon_region.eu_central_1'   => 'eu-central-1',
                    'mautic.email.config.mailer.amazon_region.eu_west_1'      => 'eu-west-1',
                    'mautic.email.config.mailer.amazon_region.eu_west_2'      => 'eu-west-2',
                    'mautic.email.config.mailer.amazon_region.sa_east_1'      => 'sa-east-1',
                    'mautic.email.config.mailer.amazon_region.us_gov_west_1'  => 'us-gov-west-1',
                ],
                'label'       => 'mautic.email.config.mailer.amazon_region',
                'required'    => false,
                'attr'        => [
                    'class'        => 'form-control',
                    'data-show-on' => '{"config_emailconfig_mailer_transport":["ses+api"]}',
                    'tooltip'      => 'mautic.email.config.mailer.amazon_region.tooltip',
                    'onchange'     => 'Mautic.disableSendTestEmailButton()',
                ],
                'placeholder' => false,
            ]
        );
    }
}
