<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Form\Type;

use Mautic\LeadBundle\Model\ListModel;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Class CampaignEventLeadSegmentsType.
 */
class CampaignEventLeadCampaignsType extends AbstractType
{
    /**
     * @var ListModel
     */
    protected $listModel;
    /**
     * CampaignEventLeadCampaignsType constructor.
     *
     * @param ListModel $listModel
     */
    public function __construct(ListModel $listModel)
    {
        $this->listModel = $listModel;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('campaigns',
            'campaign_list', [
            'label'      => 'mautic.lead.lead.events.campaigns',
            'label_attr' => ['class' => 'control-label'],
            'attr'       => [
                'class' => 'form-control',
            ],
            'required' => false,
        ]);

        $builder->add(
            'dataAddedLimit',
            'yesno_button_group',
            [
                'label' => 'mautic.lead.lead.events.campaigns.date.added.limit',
                'data'  => (isset($options['data']['dataAddedLimit'])) ? $options['data']['dataAddedLimit'] : false,
            ]
        );

        $builder->add(
            'expr',
            'choice',
            [
                'label'    => 'mautic.lead.lead.events.campaigns.expression',
                'multiple' => false,
                'choices'  => $this->listModel->getOperatorsForFieldType(
                    [
                        'include' => [
                            '=',
                            '!=',
                            'gt',
                            'gte',
                            'lt',
                            'lte',
                        ],
                    ]),
                'required'   => false,
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'        => 'form-control',
                    'data-show-on' => '{"campaignevent_properties_dataAddedLimit_1":"checked"}',
                ],
            ]
        );

        $builder->add('date', 'datetime', [
            'widget'     => 'single_text',
            'label'      => 'mautic.lead.lead.events.campaigns.date',
            'label_attr' => ['class' => 'control-label'],
            'attr'       => [
                'class'        => 'form-control',
                'data-toggle'  => 'date',
                'data-show-on' => '{"campaignevent_properties_dataAddedLimit_1":"checked"}',
            ],
            'format'   => 'yyyy-MM-dd',
            'required' => false,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'campaignevent_lead_campaigns';
    }
}
