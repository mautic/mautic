<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticCrmBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Class IntegrationCampaignsTaskType.
 */
class IntegrationCampaignsTaskType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $integrationObject = $options['helper']->getIntegrationObject('Connectwise');
        $builder->add(
            'activity_name',
            'text',
            [
                'label'      => 'mautic.connectwise.activity.name',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => ['class' => 'form-control'],
                'required'   => false,
            ]
        );

        $activityTypes = $integrationObject->getActivityTypes();
        $builder->add(
            'campaign_activity_type',
            'choice',
            [
                'choices' => $activityTypes,
                'attr'    => [
                    'class' => 'form-control', ],
                'label'    => 'mautic.plugin.integration.campaigns.connectwise.activity.type',
                'required' => false,
            ]
        );
        $members = $integrationObject->getMembers();
        $builder->add(
            'campaign_members',
            'choice',
            [
                'choices' => $members,
                'attr'    => [
                    'class' => 'form-control', ],
                'label'    => 'mautic.plugin.integration.campaigns.connectwise.members',
                'required' => false,
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setOptional(['helper']);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'integration_campaign_task';
    }
}
