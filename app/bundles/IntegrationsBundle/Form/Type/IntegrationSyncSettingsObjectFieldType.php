<?php

declare(strict_types=1);

/*
 * @copyright   2018 Mautic, Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\IntegrationsBundle\Form\Type;

use MauticPlugin\IntegrationsBundle\Exception\InvalidFormOptionException;
use MauticPlugin\IntegrationsBundle\Mapping\MappedFieldInfoInterface;
use MauticPlugin\IntegrationsBundle\Sync\DAO\Mapping\ObjectMappingDAO;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class IntegrationSyncSettingsObjectFieldType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     *
     * @throws InvalidFormOptionException
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $field = $options['field'];
        if (!$field instanceof MappedFieldInfoInterface) {
            throw new InvalidFormOptionException('field must contain an instance of MappedFieldInfoInterface');
        }

        $builder->add(
            'mappedField',
            ChoiceType::class,
            [
                'label'          => false,
                'choices'        => $options['mauticFields'],
                'required'       => $field->showAsRequired(),
                'empty_value'    => '',
                'error_bubbling' => false,
                'attr'           => [
                    'class'            => 'form-control integration-mapped-field',
                    'data-placeholder' => $options['placeholder'],
                    'data-object'      => $options['object'],
                    'data-integration' => $options['integration'],
                    'data-field'       => $field->getName(),
                ],
            ]
        );

        $choices = [];
        if ($field->isBidirectionalSyncEnabled()) {
            $choices[ObjectMappingDAO::SYNC_BIDIRECTIONALLY] = 'mautic.integration.sync_direction_bidirectional';
        }
        if ($field->isToIntegrationSyncEnabled()) {
            $choices[ObjectMappingDAO::SYNC_TO_INTEGRATION] = 'mautic.integration.sync_direction_integration';
        }
        if ($field->isToMauticSyncEnabled()) {
            $choices[ObjectMappingDAO::SYNC_TO_MAUTIC] = 'mautic.integration.sync_direction_mautic';
        }

        if (empty($choices)) {
            throw new InvalidFormOptionException('field "'.$field->getName().'" must allow at least 1 direction for sync');
        }

        reset($choices);
        $defaultChoice = key($choices);

        $builder->add(
            'syncDirection',
            ChoiceType::class,
            [
                'choices'    => $choices,
                'label'      => false,
                'empty_data' => $defaultChoice,
                'attr'       => [
                    'class'            => 'integration-sync-direction',
                    'data-object'      => $options['object'],
                    'data-integration' => $options['integration'],
                    'data-field'       => $field->getName(),
                ],
            ]
        );
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired(
            [
                'mauticFields',
                'placeholder',
                'integration',
                'object',
                'field',
            ]
        );
    }
}
