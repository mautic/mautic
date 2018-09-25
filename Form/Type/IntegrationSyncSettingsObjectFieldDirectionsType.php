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

class IntegrationSyncSettingsObjectFieldDirectionsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        foreach ($options['integrationFields'] as $field) {
            $builder->add(
                $field,
                ChoiceType::class,
                [
                    'choices' => [
                        ObjectMappingDAO::SYNC_TO_INTEGRATION  => 'mautic.integration.sync_direction_integration',
                        ObjectMappingDAO::SYNC_TO_MAUTIC       => 'mautic.integration.sync_direction_mautic',
                        ObjectMappingDAO::SYNC_BIDIRECTIONALLY => 'mautic.integration.sync_direction_bidirectional',
                    ],
                    'label'   => false,
                    'data'    => (empty($options['data'])) ? ObjectMappingDAO::SYNC_BIDIRECTIONALLY : $options['data']
                ]
            );
        }
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired(
            [
                'integrationFields'
            ]
        );
    }
}