<?php

/*
 * @copyright   2018 Mautic, Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\IntegrationsBundle\Controller;

use Mautic\CoreBundle\Controller\AbstractFormController;
use Mautic\PluginBundle\Entity\Integration;
use MauticPlugin\IntegrationsBundle\Event\FormLoadEvent;
use MauticPlugin\IntegrationsBundle\Exception\IntegrationNotFoundException;
use MauticPlugin\IntegrationsBundle\Exception\RequiredFieldsMissingException;
use MauticPlugin\IntegrationsBundle\Form\Type\IntegrationConfigType;
use MauticPlugin\IntegrationsBundle\Helper\ConfigIntegrationsHelper;
use MauticPlugin\IntegrationsBundle\Helper\FieldMergerHelper;
use MauticPlugin\IntegrationsBundle\Integration\BasicIntegration;
use MauticPlugin\IntegrationsBundle\Integration\Interfaces\ConfigFormFeaturesInterface;
use MauticPlugin\IntegrationsBundle\Integration\Interfaces\ConfigFormInterface;
use MauticPlugin\IntegrationsBundle\Integration\Interfaces\ConfigFormSyncInterface;
use MauticPlugin\IntegrationsBundle\SyncEvents;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;

class ConfigController extends AbstractFormController
{
    /**
     * @var Request
     */
    protected $request;

    /**
     * @var Form
     */
    private $form;

    /**
     * @var BasicIntegration|ConfigFormInterface
     */
    private $integrationObject;

    /**
     * @var Integration
     */
    private $integrationConfiguration;

    /**
     * @var ConfigIntegrationsHelper
     */
    private $integrationsHelper;

    /**
     * @param Request $request
     * @param string  $integration
     *
     * @return array|JsonResponse|RedirectResponse|Response
     */
    public function editAction(Request $request, string $integration)
    {
        // Check ACL
        if (!$this->get('mautic.security')->isGranted('plugin:plugins:manage')) {
            return $this->accessDenied();
        }

        // Find the integration
        /** @var ConfigIntegrationsHelper $integrationsHelper */
        $this->integrationsHelper = $this->get('mautic.integrations.helper.config_integrations');
        try {
            $this->integrationObject        = $this->integrationsHelper->getIntegration($integration);
            $this->integrationConfiguration = $this->integrationObject->getIntegrationConfiguration();
        } catch (IntegrationNotFoundException $exception) {
            return $this->notFound();
        }

        $dispatcher = $this->get('event_dispatcher');
        $event      = new FormLoadEvent($this->integrationConfiguration);
        $dispatcher->dispatch(SyncEvents::INTEGRATION_CONFIG_FORM_LOAD, $event);

        // Set the request for private methods
        $this->request = $request;

        // Create the form
        $this->form = $this->getForm();

        if (Request::METHOD_POST === $request->getMethod()) {
            return $this->submitForm();
        }

        // Clear the session of previously stored fields in case it got stuck
        /** @var Session $session */
        $session = $this->get('session');
        $session->remove("$integration-fields");

        return $this->showForm();
    }

    /**
     * @return JsonResponse|Response
     */
    private function submitForm()
    {
        if ($cancelled = $this->isFormCancelled($this->form)) {
            return $this->closeForm();
        }

        // Get the fields before the form binds partial data due to pagination
        $settings      = $this->integrationConfiguration->getFeatureSettings();
        $fieldMappings = (isset($settings['sync']['fieldMappings'])) ? $settings['sync']['fieldMappings'] : [];

        // Submit the form
        $this->form->handleRequest($this->request);

        if ($this->integrationObject instanceof ConfigFormSyncInterface) {
            $integration   = $this->integrationObject->getName();
            $settings      = $this->integrationConfiguration->getFeatureSettings();
            $session       = $this->get('session');
            $updatedFields = $session->get("$integration-fields", []);

            $fieldMerger = new FieldMergerHelper($this->integrationObject, $fieldMappings);

            foreach ($updatedFields as $object => $fields) {
                $fieldMerger->mergeSyncFieldMapping($object, $fields);
            }

            $this->validateRequiredFields($fieldMerger, $settings['sync']['objects']);

            $settings['sync']['fieldMappings'] = $fieldMerger->getFieldMappings();

            $this->integrationConfiguration->setFeatureSettings($settings);
        }

        // Show the form if there are errors
        if (!$this->form->isValid()) {
            return $this->showForm();
        }

        // Save the integration configuration
        $this->integrationsHelper->saveIntegrationConfiguration($this->integrationConfiguration);

        // Show the form if the apply button was clicked
        if ($this->isFormApplied($this->form)) {
            // Regenerate the form
            $this->form = $this->getForm();

            return $this->showForm();
        }

        // Otherwise close the modal
        return $this->closeForm();
    }

    /**
     * @return Form
     */
    private function getForm()
    {
        return $this->get('form.factory')->create(
            $this->integrationObject->getConfigFormName() ? $this->integrationObject->getConfigFormName() : IntegrationConfigType::class,
            $this->integrationConfiguration,
            [
                'action'      => $this->generateUrl('mautic_integration_config', ['integration' => $this->integrationObject->getName()]),
                'integration' => $this->integrationObject->getName(),
            ]
        );
    }

    /**
     * @return JsonResponse|Response
     */
    private function showForm()
    {
        return $this->delegateView(
            [
                'viewParameters'  => [
                    'integrationObject' => $this->integrationObject,
                    'form'              => $this->setFormTheme($this->form, 'IntegrationsBundle:Config:form.html.php'),
                    'activeTab'         => $this->request->get('activeTab'),
                ],
                'contentTemplate' => $this->integrationObject->getConfigFormContentTemplate()
                    ? $this->integrationObject->getConfigFormContentTemplate()
                    : 'IntegrationsBundle:Config:form.html.php',
                'passthroughVars' => [
                    'activeLink'    => '#mautic_plugin_index',
                    'mauticContent' => 'integrationsConfig',
                    'route'         => false,
                ],
            ]
        );
    }

    /**
     * @return JsonResponse
     */
    private function closeForm()
    {
        /** @var Session $session */
        $session = $this->get('session');
        $session->remove("{$this->integrationObject->getName()}-fields");

        return new JsonResponse(
            [
                'closeModal'    => 1,
                'enabled'       => $this->integrationConfiguration->getIsPublished(),
                'name'          => $this->integrationConfiguration->getName(),
                'mauticContent' => 'integrationsConfig',
            ]
        );
    }

    /**
     * @param FieldMergerHelper $fieldMergerHelper
     * @param array             $objects
     */
    private function validateRequiredFields(FieldMergerHelper $fieldMergerHelper, array $objects)
    {
        if (!$this->integrationConfiguration->getIsPublished()) {
            // Don't bind form errors if the integration is not published
            return;
        }

        $features = $this->integrationConfiguration->getSupportedFeatures();
        if (!in_array(ConfigFormFeaturesInterface::FEATURE_SYNC, $features)) {
            // Don't bind form errors if sync is not enabled
            return;
        }

        foreach ($objects as $object) {
            $hasMissingFields  = false;
            $errorsOnGivenPage = false;

            $missingFields = $fieldMergerHelper->findMissingRequiredFieldMappings($object);
            if (!$hasMissingFields && !empty($missingFields)) {
                $hasMissingFields = true;
            }

            foreach ($missingFields as $field) {
                if (!isset($this->form['featureSettings']['sync']['fieldMappings'][$object][$field])) {
                    continue;
                }

                $errorsOnGivenPage = true;

                /** @var Form $formField */
                $formField = $this->form['featureSettings']['sync']['fieldMappings'][$object][$field]['mappedField'];
                $formField->addError(
                    new FormError(
                        $this->get('translator')->trans('mautic.core.value.required', [], 'validators')
                    )
                );
            }

            if (!$errorsOnGivenPage && $hasMissingFields) {
                // A hidden page has required fields that are missing so we have to tell the form there is an error
                /** @var Form $formField */
                $formField = $this->form['featureSettings']['sync']['fieldMappings'][$object];
                $formField->addError(
                    new FormError(
                        $this->get('translator')->trans('mautic.core.value.required', [], 'validators')
                    )
                );
            }
        }
    }
}
