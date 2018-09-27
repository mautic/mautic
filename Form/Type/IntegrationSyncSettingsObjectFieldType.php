<?php

/*
 * @copyright   2018 Mautic, Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\IntegrationsBundle\Form\Type;

use MauticPlugin\IntegrationsBundle\Sync\DAO\Mapping\ObjectMappingDAO;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class IntegrationSyncSettingsObjectFieldType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'mappedField',
            ChoiceType::class,
            [
                'label'       => false,
                'choices'     => $options['mauticFields'],
                'required'    => !empty($options['required']),
                'empty_value' => '',
                'attr'        => [
                    'class'            => 'form-control',
                    'data-placeholder' => $options['placeholder'],
                ],
            ]
        );

        $builder->add(
            'syncDirection',
            ChoiceType::class,
            [
                'choices' => [
                    ObjectMappingDAO::SYNC_TO_INTEGRATION  => 'mautic.integration.sync_direction_integration',
                    ObjectMappingDAO::SYNC_TO_MAUTIC       => 'mautic.integration.sync_direction_mautic',
                    ObjectMappingDAO::SYNC_BIDIRECTIONALLY => 'mautic.integration.sync_direction_bidirectional',
                ],
                'label'   => false,
                'data'    => (empty($options['data']['syncDirection'])) ? ObjectMappingDAO::SYNC_BIDIRECTIONALLY : $options['data']['syncDirection']
            ]
        );
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired(
            [
                'mauticFields',
                'placeholder'
            ]
        );
    }
}