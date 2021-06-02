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

use DeviceDetector\Parser\Device\AbstractDeviceParser as DeviceParser;
use DeviceDetector\Parser\OperatingSystem;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;

class CampaignEventLeadDeviceType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'device_type',
            ChoiceType::class,
            [
                'label'             => 'mautic.lead.campaign.event.device_type',
                'label_attr'        => ['class' => 'control-label'],
                'multiple'          => true,
                'choices'           => array_combine((DeviceParser::getAvailableDeviceTypeNames()), (DeviceParser::getAvailableDeviceTypeNames())),
                'attr'              => ['class' => 'form-control'],
                'required'          => false,
            ]
        );

        $builder->add(
            'device_brand',
            ChoiceType::class,
            [
                'label'             => 'mautic.lead.campaign.event.device_brand',
                'label_attr'        => ['class' => 'control-label'],
                'multiple'          => true,
                'choices'           => array_flip(DeviceParser::$deviceBrands),
                'attr'              => ['class' => 'form-control'],
                'required'          => false,
            ]
        );

        $builder->add(
            'device_os',
            ChoiceType::class,
            [
                'label'             => 'mautic.lead.campaign.event.device_os',
                'label_attr'        => ['class' => 'control-label'],
                'multiple'          => true,
                'choices'           => array_combine((array_keys(OperatingSystem::getAvailableOperatingSystemFamilies())), array_keys(OperatingSystem::getAvailableOperatingSystemFamilies())),
                'attr'              => ['class' => 'form-control'],
                'required'          => false,
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'campaignevent_lead_device';
    }
}
