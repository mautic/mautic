<?php

/*
 * @copyright   2018 Mautic Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://www.mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\IntegrationsBundle\Helper;


use Mautic\LeadBundle\Model\FieldModel;
use MauticPlugin\IntegrationsBundle\Integration\Interfaces\BasicInterface;
use MauticPlugin\IntegrationsBundle\Integration\Interfaces\ConfigFormFeaturesInterface;
use MauticPlugin\IntegrationsBundle\Integration\Interfaces\ConfigFormSyncInterface;
use MauticPlugin\IntegrationsBundle\Sync\Exception\ObjectNotFoundException;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormError;
use Symfony\Component\Translation\TranslatorInterface;

class FieldValidationHelper
{
    /**
     * @var FieldModel
     */
    private $fieldModel;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var ConfigFormSyncInterface|BasicInterface
     */
    private $integrationObject;

    /**
     * @var array
     */
    private $fieldMappings;

    /**
     * FieldValidationHelper constructor.
     *
     * @param FieldModel          $fieldModel
     * @param TranslatorInterface $translator
     */
    public function __construct(FieldModel $fieldModel, TranslatorInterface $translator)
    {
        $this->fieldModel = $fieldModel;
        $this->translator = $translator;
    }

    /**
     * @param Form                    $form
     * @param ConfigFormSyncInterface $integrationObject
     * @param array                   $fieldMappings
     */
    public function validateRequiredFields(Form $form, ConfigFormSyncInterface $integrationObject, array $fieldMappings)
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
        $this->fieldMappings     = $fieldMappings;

        $settings = $integrationConfiguration->getFeatureSettings();
        foreach ($settings['sync']['objects'] as $object) {
            $objectFieldMappings = (isset($fieldMappings[$object])) ? $fieldMappings[$object] : [];
            $fieldMappingForm    = $form['featureSettings']['sync']['fieldMappings'][$object];

            try {
                $missingFields = $this->findMissingIntegrationRequiredFieldMappings($object, $objectFieldMappings);
                $this->validateIntegrationRequiredFields($fieldMappingForm, $missingFields);

                $this->validateMauticRequiredFields($fieldMappingForm, $object, $objectFieldMappings);
            } catch (\Exception $exception) {
                $fieldMappingForm->addError(new FormError($exception->getMessage()));
            }
        }
    }

    /**
     * @param Form  $fieldMappingsForm
     * @param array $missingFields
     */
    private function validateIntegrationRequiredFields(Form $fieldMappingsForm, array $missingFields)
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

    /**
     * @param string $object
     * @param array  $mappedFields
     *
     * @return array
     */
    private function findMissingIntegrationRequiredFieldMappings(string $object, array $mappedFields)
    {
        $requiredFields = $this->integrationObject->getRequiredFieldsForMapping($object);

        $missingFields = [];
        foreach ($requiredFields as $field => $label) {
            if (empty($mappedFields[$field]['mappedField'])) {
                $missingFields[] = $field;
            }
        }

        return $missingFields;
    }

    /**
     * @param Form   $fieldMappingsForm
     * @param string $object
     * @param array  $objectFieldMappings
     *
     * @throws ObjectNotFoundException
     */
    private function validateMauticRequiredFields(Form $fieldMappingsForm, string $object, array $objectFieldMappings)
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
     * @param string $object
     * @param array  $objectFieldMappings
     *
     * @return array
     * @throws ObjectNotFoundException
     */
    private function findMissingInternalRequiredFieldMappings(string $object, array $objectFieldMappings)
    {
        $mappedObjects = $this->integrationObject->getSyncMappedObjects();

        if (!isset($mappedObjects[$object])) {
            throw new ObjectNotFoundException($object);
        }

        $requiredFields = $this->fieldModel->getFieldList(
            false,
            false,
            [
                'isPublished' => true,
                'isRequired'  => true,
                'object'      => $mappedObjects[$object]
            ]
        );

        $uniqueIdentifierFields = $this->fieldModel->getUniqueIdentifierFields(
            [
                'isPublished' => true,
                'object'      => $mappedObjects[$object]
            ]
        );

        $requiredFields = array_merge($requiredFields, $uniqueIdentifierFields);

        // Get Mautic mapped fields
        $mauticMappedFields = [];
        foreach ($objectFieldMappings as $field => $mapping) {
            if (empty($mapping['mappedField'])) {
                continue;
            }

            $mauticMappedFields[$mapping['mappedField']] = true;
        }
        return array_diff_key($requiredFields, $mauticMappedFields);
    }
}