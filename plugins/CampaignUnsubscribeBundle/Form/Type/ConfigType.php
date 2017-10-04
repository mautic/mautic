<?php

/*
 * @copyright   2017 Partout D.N.A. All rights reserved
 * @author      Partout D.N.A.
 *
 * @link        https://partout.nl
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\CampaignUnsubscribeBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Class ConfigType
 */
class ConfigType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'campaign_unsubscribe_logo_url',
            'text',
            [
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'   => 'form-control',
                ],
                'label' => 'plugin.campaignunsubscribe.config.logo_url.label',
                'data' => empty($options['data']['campaign_unsubscribe_logo_url']) ? false : $options['data']['campaign_unsubscribe_logo_url']
            ]
        );


        $builder->add(
            'campaign_unsubscribe_remove_campaign_donotcontact',
            'yesno_button_group',
            [
                'label' => 'plugin.campaignunsubscribe.config.remove_campaign_donotcontact.label',
                'data' => empty($options['data']['campaign_unsubscribe_remove_campaign_donotcontact']) ? false : true,
                'attr' => [
                    'tooltip' => 'plugin.campaignunsubscribe.config.remove_campaign_donotcontact.tooltip'
                ]
            ]
        );

        $builder->add(
            'campaign_unsubscribe_message_title',
            'textarea',
            [
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'   => 'form-control',
                ],
                'label' => 'plugin.campaignunsubscribe.config.message_title.label',
                'data' => empty($options['data']['campaign_unsubscribe_message_title']) ? false : $options['data']['campaign_unsubscribe_message_title']
            ]
        );

        $builder->add(
            'campaign_unsubscribe_message_body',
            'textarea',
            [
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'   => 'form-control',
                ],
                'label' => 'plugin.campaignunsubscribe.config.message_body.label',
                'data' => empty($options['data']['campaign_unsubscribe_message_body']) ? false : $options['data']['campaign_unsubscribe_message_body']
            ]
        );

        $builder->add(
            'campaign_unsubscribe_message_body_no_campaigns',
            'textarea',
            [
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'   => 'form-control',
                ],
                'label' => 'plugin.campaignunsubscribe.config.campaign_unsubscribe_message_body_no_campaigns.label',
                'data' => empty($options['data']['campaign_unsubscribe_message_body_no_campaigns']) ? false : $options['data']['campaign_unsubscribe_message_body_no_campaigns']
            ]
        );

        $builder->add(
            'campaign_unsubscribe_campaign_list_label',
            'text',
            [
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'   => 'form-control',
                ],
                'label' => 'plugin.campaignunsubscribe.config.campaign_list_label.label',
                'data' => empty($options['data']['campaign_unsubscribe_campaign_list_label']) ? false : $options['data']['campaign_unsubscribe_campaign_list_label']
            ]
        );

        $builder->add(
            'campaign_unsubscribe_donotcontact_label',
            'text',
            [
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'   => 'form-control',
                ],
                'label' => 'plugin.campaignunsubscribe.config.donotcontact_label.label',
                'data' => empty($options['data']['campaign_unsubscribe_donotcontact_label']) ? false : $options['data']['campaign_unsubscribe_donotcontact_label']
            ]
        );

        $builder->add(
            'campaign_unsubscribe_submit_label',
            'text',
            [
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'   => 'form-control',
                ],
                'label' => 'plugin.campaignunsubscribe.config.submit_label.label',
                'data' => empty($options['data']['campaign_unsubscribe_submit_label']) ? false : $options['data']['campaign_unsubscribe_submit_label']
            ]
        );

        $builder->add(
            'campaign_unsubscribe_confirmation_title',
            'textarea',
            [
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'   => 'form-control',
                ],
                'label' => 'plugin.campaignunsubscribe.config.confirmation_title.label',
                'data' => empty($options['data']['campaign_unsubscribe_confirmation_title']) ? false : $options['data']['campaign_unsubscribe_confirmation_title']
            ]
        );

        $builder->add(
            'campaign_unsubscribe_confirmation_body',
            'textarea',
            [
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'   => 'form-control',
                ],
                'label' => 'plugin.campaignunsubscribe.config.confirmation_body.label',
                'data' => empty($options['data']['campaign_unsubscribe_confirmation_body']) ? false : $options['data']['campaign_unsubscribe_confirmation_body']
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'campaign_unsubscribe_config';
    }
}