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

use Mautic\LeadBundle\Helper\FormFieldHelper;
use Mautic\LeadBundle\Model\LeadModel;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Validator\Constraints\NotBlank;
use DeviceDetector\Parser\Device\DeviceParserAbstract as DeviceParser;
use DeviceDetector\Parser\OperatingSystem;

/**
 * Class CampaignEventLeadDeviceType.
 */
class CampaignEventLeadDeviceType extends AbstractType
{

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'device_type',
            'choice',
            [
                'label'       => 'mautic.lead.campaign.event.device_type',
                'label_attr'  => ['class' => 'control-label'],
                'multiple'    => true,
                'choices'     => array_combine((DeviceParser::getAvailableDeviceTypeNames()), (DeviceParser::getAvailableDeviceTypeNames())),
                'attr'        => [
                    'class'   => 'form-control'
                ],
                'required'    => false,
            ]
        );

        $builder->add(
            'device_brand',
            'choice',
            [
                'label'       => 'mautic.lead.campaign.event.device_brand',
                'label_attr'  => ['class' => 'control-label'],
                'multiple'    => true,
                'choices'     =>  DeviceParser::$deviceBrands,
                'attr'        => [
                    'class'   => 'form-control'
                ],
                'required'    => false,
            ]
        );

        $builder->add(
            'device_os',
            'choice',
            [
                'label'       => 'mautic.lead.campaign.event.device_os',
                'label_attr'  => ['class' => 'control-label'],
                'multiple'    => true,
                'choices'     => array_combine((array_keys(OperatingSystem::getAvailableOperatingSystemFamilies())), array_keys(OperatingSystem::getAvailableOperatingSystemFamilies())),
                'attr'        => [
                    'class'   => 'form-control'
                ],
                'required'    => false,
            ]
        );

    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'campaignevent_lead_device';
    }
}
