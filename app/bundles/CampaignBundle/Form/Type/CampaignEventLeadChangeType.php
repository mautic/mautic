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
 * Class CampaignEventLeadChangeType.
 */
class CampaignEventLeadChangeType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $data = (isset($options['data']['action'])) ? $options['data']['action'] : 'added';
        $builder->add('action', 'button_group', [
            'choices' => [
                'added'   => 'mautic.campaign.form.trigger_leadchanged_added',
                'removed' => 'mautic.campaign.form.trigger_leadchanged_removed',
            ],
            'expanded'    => true,
            'multiple'    => false,
            'label_attr'  => ['class' => 'control-label'],
            'label'       => 'mautic.campaign.form.trigger_leadchanged',
            'empty_value' => false,
            'required'    => false,
            'data'        => $data,
        ]);

        $builder->add('campaigns', 'campaign_list', [
            'label'      => 'mautic.campaign.form.limittocampaigns',
            'label_attr' => ['class' => 'control-label'],
            'attr'       => [
                'class'   => 'form-control',
                'tooltip' => 'mautic.campaign.form.limittocampaigns_descr',
            ],
            'required' => false,
        ]);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'campaignevent_leadchange';
    }
}
