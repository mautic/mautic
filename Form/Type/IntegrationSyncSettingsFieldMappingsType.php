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


use MauticPlugin\IntegrationsBundle\Exception\InvalidFormOptionException;
use MauticPlugin\IntegrationsBundle\Integration\Interfaces\ConfigFormSyncInterface;
use MauticPlugin\IntegrationsBundle\Sync\DAO\Mapping\MappingManualDAO;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatorInterface;

class IntegrationSyncSettingsFieldMappingsType extends AbstractType
{
    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * IntegrationSyncSettingsFieldMappingsType constructor.
     *
     * @param TranslatorInterface $translator
     */
    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     *
     * @throws InvalidFormOptionException
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if (!is_array($options['objects'])) {
            throw new InvalidFormOptionException("objects must be an array");
        }

        $integrationObject = $options['integrationObject'];
        if (!$integrationObject instanceof ConfigFormSyncInterface) {
            throw new InvalidFormOptionException("integrationObject must be an instance of ConfigFormSyncInterface");
        }

        foreach ($options['objects'] as $objectName => $objectLabel) {
            $requiredFields = $integrationObject->getRequiredFieldsForMapping($objectName);
            $optionalFields = $integrationObject->getOptionalFieldsForMapping($objectName);

            $builder->add(
                $objectName,
                IntegrationSyncSettingsObjectFieldMappingType::class,
                [
                    'label'                     => $this->translator->trans('mautic.integration.sync_field_mapping', ['object' => $objectLabel]),
                    'requiredIntegrationFields' => $requiredFields,
                    'optionalIntegrationFields' => $optionalFields,
                    'mauticFields'              => $this->getMauticFields($objectName),
                ]
            );

            $builder->add(
                $objectName,
                IntegrationSyncSettingsObjectFieldDirectionsType::class,
                [
                    'label'             => false,
                    'integrationFields' => array_merge(
                        array_keys($requiredFields),
                        array_keys($optionalFields)
                    ),
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
                'integrationObject',
                'objects'
            ]
        );
    }

    /**
     * @param string $object
     *
     * @return array
     */
    private function getMauticFields(string $object): array
    {
        // @todo
        return [];
    }
}