<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Form\Type;

use Mautic\IntegrationsBundle\Exception\IntegrationNotFoundException;
use Mautic\IntegrationsBundle\Integration\Interfaces\ConfigFormSyncInterface;
use Mautic\IntegrationsBundle\Integration\Interfaces\IntegrationInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class IntegrationSyncSettingsType extends AbstractType
{
    /**
     * @throws IntegrationNotFoundException
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $integrationObject = $options['integrationObject'];
        if (!$integrationObject instanceof IntegrationInterface || !$integrationObject instanceof ConfigFormSyncInterface) {
            throw new IntegrationNotFoundException("{$options['integrationObject']} is not recognized");
        }

        // Build field mapping
        $objects = $integrationObject->getSyncConfigObjects();

        $builder->add(
            'objects',
            ChoiceType::class,
            [
                'choices'     => array_flip($objects),
                'expanded'    => true,
                'multiple'    => true,
                'label'       => 'mautic.integration.sync_objects',
                'label_attr'  => ['class' => 'control-label'],
                'placeholder' => [],
                'required'    => false,
            ]
        );

        // @todo
        /*
        $builder->add(
            'updateBlanks',
            YesNoButtonGroupType::class,
            [
                'label'       => 'mautic.integration.sync.update_blanks',
                'label_attr'  => ['class' => 'control-label'],
                'placeholder' => false,
                'required'    => false,
                'data'        => !empty($options['data']['updateBlanks'])
            ]
        );
        */

        $builder->add(
            'fieldMappings',
            IntegrationSyncSettingsFieldMappingsType::class,
            [
                'label'             => false,
                'integrationObject' => $integrationObject,
                'objects'           => $objects,
            ]
        );

        if ($customSettings = $integrationObject->getSyncConfigFormName()) {
            $builder->add(
                'integration',
                $customSettings,
                [
                    'label' => false,
                ]
            );
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired(
            [
                'integrationObject',
            ]
        );
    }
}
