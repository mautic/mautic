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

use Mautic\CoreBundle\Factory\MauticFactory;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Class FormSubmitActionPointsChangeType.
 */
class FormSubmitActionPointsChangeType extends AbstractType
{
    private $factory;

    /**
     * @param MauticFactory $factory
     */
    public function __construct(MauticFactory $factory)
    {
        $this->factory = $factory;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('operator', 'choice', [
            'label'      => 'mautic.lead.lead.submitaction.operator',
            'attr'       => ['class' => 'form-control'],
            'label_attr' => ['class' => 'control-label'],
            'choices'    => [
                'plus'   => 'mautic.lead.lead.submitaction.operator_plus',
                'minus'  => 'mautic.lead.lead.submitaction.operator_minus',
                'times'  => 'mautic.lead.lead.submitaction.operator_times',
                'divide' => 'mautic.lead.lead.submitaction.operator_divide',
            ],
        ]);

        $default = (empty($options['data']['points'])) ? 0 : (int) $options['data']['points'];
        $builder->add('points', 'number', [
            'label'      => 'mautic.lead.lead.submitaction.points',
            'attr'       => ['class' => 'form-control'],
            'label_attr' => ['class' => 'control-label'],
            'precision'  => 0,
            'data'       => $default,
        ]);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'lead_submitaction_pointschange';
    }
}
