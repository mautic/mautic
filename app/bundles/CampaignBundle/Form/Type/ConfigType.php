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
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Class ConfigType.
 */
class ConfigType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'campaign_time_wait_on_event_false',
            'choice',
            [
                'label'      => 'mautic.campaignconfig.campaign_time_wait_on_event_false',
                'label_attr' => ['class' => 'control-label'],
                'data'       => $options['data']['campaign_time_wait_on_event_false'],
                'choices'    => [
                    'null'  => '0 mn',
                    'PT15M' => '15 mn',
                    'PT30M' => '30 mn',
                    'PT45M' => '45 mn',
                    'PT1H'  => '1 h',
                    'PT2H'  => '2 h',
                    'PT4H'  => '4 h',
                    'PT8H'  => '8 h',
                    'PT12H' => '12 h',
                    'PT1D'  => '24 h',
                    'PT3D'  => '3 days',
                    'PT5D'  => '5 days',
                    'PT14D' => '1 week',
                    'P3M'   => '3 months',
                ],
                'attr' => [
                    'class'   => 'form-control',
                    'tooltip' => 'mautic.campaignconfig.campaign_time_wait_on_event_false_tooltip',
                ],
                'required' => false,
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'campaignconfig';
    }
}
