<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Class DashboardEmailsInTimeWidgetType.
 */
class DashboardEmailsInTimeWidgetType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'flag',
            'choice',
            [
                'label'      => 'mautic.email.flag.filter',
                'choices'    => [
                    ''                           => 'mautic.email.flag.sent',
                    'opened'                     => 'mautic.email.flag.opened',
                    'failed'                     => 'mautic.email.flag.failed',
                    'sent_and_opened'            => 'mautic.email.flag.sent.and.opened',
                    'sent_and_opened_and_failed' => 'mautic.email.flag.sent.and.opened.and.failed',
                ],
                'label_attr' => ['class' => 'control-label'],
                'attr'       => ['class' => 'form-control'],
                'empty_data' => '',
                'required'   => false,
            ]
        );

        $builder->add(
            'companyId',
            'company_list',
            [
                'label'       => 'mautic.email.companyId.filter',
                'label_attr'  => ['class' => 'control-label'],
                'attr'        => ['class' => 'form-control'],
                'empty_value' => '',
                'required'    => false,
                'multiple'    => false,
                'modal_route' => null,
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
                'label'       => 'mautic.email.segmentId.filter',
                'label_attr'  => ['class' => 'control-label'],
                'attr'        => ['class' => 'form-control'],
                'empty_value' => '',
                'required'    => false,
            ]
        );
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'email_dashboard_emails_in_time_widget';
    }
}
