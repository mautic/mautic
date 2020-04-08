<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CampaignBundle\Form\Type;

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
            'campaign_time_wait_on_event_false',
            ChoiceType::class,
            [
                'label'      => 'mautic.campaignconfig.campaign_time_wait_on_event_false',
                'label_attr' => ['class' => 'control-label'],
                'data'       => $options['data']['campaign_time_wait_on_event_false'],
                'choices'    => [
                    '0 mn'       => 'null',
                    '15 mn'      => 'PT15M',
                    '30 mn'      => 'PT30M',
                    '45 mn'      => 'PT45M',
                    '1 h'        => 'PT1H',
                    '2 h'        => 'PT2H',
                    '4 h'        => 'PT4H',
                    '8 h'        => 'PT8H',
                    '12 h'       => 'PT12H',
                    '24 h'       => 'PT1D',
                    '3 days'     => 'PT3D',
                    '5 days'     => 'PT5D',
                    '1 week'     => 'PT14D',
                    '3 months'   => 'P3M',
                ],
                'attr'              => [
                    'class'   => 'form-control',
                    'tooltip' => 'mautic.campaignconfig.campaign_time_wait_on_event_false_tooltip',
                ],
                'required' => false,
            ]
        );
    }

    public function getBlockPrefix()
    {
        return 'campaignconfig';
    }
}
