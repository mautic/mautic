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

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
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
            'background_import_if_more_rows_than',
            NumberType::class,
            [
                'label'      => 'mautic.lead.background.import.if.more.rows.than',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'   => 'form-control',
                    'tooltip' => 'mautic.lead.background.import.if.more.rows.than.tooltip',
                ],
            ]
        );

        $builder->add(
            'import_max_runtime',
            NumberType::class,
            [
                'label'      => 'mautic.lead.import.max.runtime',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'   => 'form-control',
                    'tooltip' => 'mautic.lead.import.max.runtime.tooltip',
                ],
            ]
        );

        $statusChoices = [];
        foreach ([7, 4, 5] as $validStatus) {
            $statusChoices[$validStatus] = 'mautic.lead.import.status.'.$validStatus;
        }
        $builder->add(
            'import_max_runtime_status',
            ChoiceType::class,
            [
                'choices'  => $statusChoices,
                'label'    => 'mautic.lead.import.max.runtime.status',
                'required' => true,
                'attr'     => [
                    'class'   => 'form-control',
                    'tooltip' => 'mautic.lead.import.max.runtime.status.tooltip',
                ],
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'leadconfig';
    }
}
