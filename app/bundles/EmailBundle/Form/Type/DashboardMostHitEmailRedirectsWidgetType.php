<?php

namespace Mautic\EmailBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Class DashboardMostHitEmailRedirectsWidgetType.
 */
class DashboardMostHitEmailRedirectsWidgetType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'companyId',
            'company_list',
            [
                'label'       => 'mautic.email.companyId.filter',
                'label_attr'  => ['class' => 'control-label'],
                'attr'        => ['class' => 'form-control'],
                'empty_data'  => '',
                'required'    => false,
                'multiple'    => false,
                'modal_route' => null, // disable "Add new" option in ajax lookup
            ]
        );

        $builder->add(
            'campaignId',
            'campaign_list',
            [
                'label'       => 'mautic.email.campaignId.filter',
                'label_attr'  => ['class' => 'control-label'],
                'attr'        => ['class' => 'form-control'],
                'empty_data'  => '',
                'empty_value' => '',
                'required'    => false,
                'multiple'    => false,
            ]
        );

        $builder->add(
            'segmentId',
            'leadlist_choices',
            [
                'label'      => 'mautic.email.segmentId.filter',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => ['class' => 'form-control'],
                'empty_data' => '',
                'required'   => false,
            ]
        );
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'email_dashboard_most_hit_email_redirects_widget';
    }
}
