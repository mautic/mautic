<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Helper;

use Mautic\IntegrationsBundle\Integration\Interfaces\BasicInterface;
use Mautic\IntegrationsBundle\Integration\Interfaces\ConfigFormFeaturesInterface;
use Mautic\IntegrationsBundle\Integration\Interfaces\ConfigFormSyncInterface;
use Mautic\IntegrationsBundle\Sync\Exception\ObjectNotFoundException;
use Mautic\IntegrationsBundle\Sync\SyncDataExchange\Helper\FieldHelper;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormError;
use Symfony\Component\Translation\TranslatorInterface;

class FieldValidationHelper
{
    /**
     * @var FieldHelper
     */
    private $fieldHelper;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var ConfigFormSyncInterface|BasicInterface
     */
    private $integrationObject;

    public function __construct(FieldHelper $fieldHelper, TranslatorInterface $translator)
    {
        $this->fieldHelper = $fieldHelper;
        $this->translator  = $translator;
    }

    public function validateRequiredFields(Form $form, ConfigFormSyncInterface $integrationObject, array $fieldMappings): void
    {
        $integrationConfiguration = $integrationObject->getIntegrationConfiguration();
        if (!$integrationConfiguration->getIsPublished()) {
            // Don't bind form errors if the integration is not published
            return;
        }

        $features = $integrationConfiguration->getSupportedFeatures();
        if (!in_array(ConfigFormFeaturesInterface::FEATURE_SYNC, $features)) {
            // Don't bind form errors if sync is not enabled
            return;
        }

        $this->integrationObject = $integrationObject;

        $settings = $integrationConfiguration->getFeatureSettings();
        foreach ($settings['sync']['objects'] as $object) {
            $objectFieldMappings = $fieldMappings[$object] ?? [];
            $fieldMappingForm    = $form['featureSettings']['sync']['fieldMappings'][$object];

            try {
                $missingFields = $this->findMissingIntegrationRequiredFieldMappings($object, $objectFieldMappings);
                $this->validateIntegrationRequiredFields($fieldMappingForm, $missingFields);

                $this->validateMauticRequiredFields($fieldMappingForm, $object, $objectFieldMappings);
            } catch (\Throwable $exception) {
                $fieldMappingForm->addError(new FormError($exception->getMessage()));
            }
        }
    }

    private function validateIntegrationRequiredFields(Form $fieldMappingsForm, array $missingFields): void
    {
        $hasMissingFields  = false;
        $errorsOnGivenPage = false;

        if (!$hasMissingFields && !empty($missingFields)) {
            $hasMissingFields = true;
        }

        foreach ($missingFields as $field) {
            if (!isset($fieldMappingsForm[$field])) {
                continue;
            }

            $errorsOnGivenPage = true;

            /** @var Form $formField */
            $formField = $fieldMappingsForm[$field]['mappedField'];
            $formField->addError(
                new FormError(
                    $this->translator->trans('mautic.core.value.required', [], 'validators')
                )
            );
        }

        if (!$errorsOnGivenPage && $hasMissingFields) {
            // A hidden page has required fields that are missing so we have to tell the form there is an error
            $fieldMappingsForm->addError(
                new FormError(
                    $this->translator->trans('mautic.core.value.required', [], 'validators')
                )
            );
        }
    }

    private function findMissingIntegrationRequiredFieldMappings(string $object, array $mappedFields): array
    {
        $requiredFields = $this->integrationObject->getRequiredFieldsForMapping($object);

        $missingFields = [];
        foreach ($requiredFields as $field => $fieldObject) {
            if (empty($mappedFields[$field]['mappedField'])) {
                $missingFields[] = $field;
            }
        }

        return $missingFields;
    }

    /**
     * @throws ObjectNotFoundException
     */
    private function validateMauticRequiredFields(Form $fieldMappingsForm, string $object, array $objectFieldMappings): void
    {
        $missingFields = $this->findMissingInternalRequiredFieldMappings($object, $objectFieldMappings);
        if (empty($missingFields)) {
            return;
        }

        $fieldMappingsForm->addError(
            new FormError(
                $this->translator->trans(
                    'mautic.integration.sync.missing_mautic_field_mappings',
                    [
                        '%fields%' => implode(', ', $missingFields),
                    ],
                    'validators'
                )
            )
        );
    }

    /**
     * @throws ObjectNotFoundException
     */
    private function findMissingInternalRequiredFieldMappings(string $object, array $objectFieldMappings): array
    {
        $mappedObjects = $this->integrationObject->getSyncMappedObjects();

        if (!isset($mappedObjects[$object])) {
            throw new ObjectNotFoundException($object);
        }

        // Get Mautic mapped fields
        $mauticMappedFields = [];
        foreach ($objectFieldMappings as $mapping) {
            if (empty($mapping['mappedField'])) {
                continue;
            }

            $mauticMappedFields[$mapping['mappedField']] = true;
        }

        $requiredFields = $this->fieldHelper->getRequiredFields($mappedObjects[$object]);

        return array_diff_key($requiredFields, $mauticMappedFields);
    }
}
