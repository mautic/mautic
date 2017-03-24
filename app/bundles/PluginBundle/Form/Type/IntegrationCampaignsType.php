<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PluginBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Class IntegrationCampaignsType.
 */
class IntegrationCampaignsType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if (!empty($options['campaignContactStatus'])) {
            $builder->add(
                'campaign_member_status',
                'choice',
                [
                    'choices' => $options['campaignContactStatus'],
                    'attr'    => [
                        'class' => 'form-control integration-campaigns-status', ],
                    'label'       => 'mautic.plugin.integration.campaigns.member.status',
                    'empty_value' => false,
                ]

            );
        }
    }
    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            ['campaignContactStatus' => []]);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'integration_campaign_status';
    }
}
