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

namespace Mautic\IntegrationsBundle\Controller;

use Mautic\CoreBundle\Controller\AbstractFormController;
use Mautic\IntegrationsBundle\Event\ConfigSaveEvent;
use Mautic\IntegrationsBundle\Event\FormLoadEvent;
use Mautic\IntegrationsBundle\Exception\IntegrationNotFoundException;
use Mautic\IntegrationsBundle\Form\Type\IntegrationConfigType;
use Mautic\IntegrationsBundle\Helper\ConfigIntegrationsHelper;
use Mautic\IntegrationsBundle\Helper\FieldMergerHelper;
use Mautic\IntegrationsBundle\Helper\FieldValidationHelper;
use Mautic\IntegrationsBundle\Integration\BasicIntegration;
use Mautic\IntegrationsBundle\Integration\Interfaces\ConfigFormAuthInterface;
use Mautic\IntegrationsBundle\Integration\Interfaces\ConfigFormAuthorizeButtonInterface;
use Mautic\IntegrationsBundle\Integration\Interfaces\ConfigFormCallbackInterface;
use Mautic\IntegrationsBundle\Integration\Interfaces\ConfigFormFeatureSettingsInterface;
use Mautic\IntegrationsBundle\Integration\Interfaces\ConfigFormFeaturesInterface;
use Mautic\IntegrationsBundle\Integration\Interfaces\ConfigFormInterface;
use Mautic\IntegrationsBundle\Integration\Interfaces\ConfigFormSyncInterface;
use Mautic\IntegrationsBundle\IntegrationEvents;
use Mautic\PluginBundle\Entity\Integration;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\Form;
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
     * @return array|JsonResponse|RedirectResponse|Response
     */
    public function editAction(Request $request, string $integration)
    {
        // Check ACL
        if (!$this->get('mautic.security')->isGranted('plugin:plugins:manage')) {
            return $this->accessDenied();
        }

        // Find the integration
        /* @var ConfigIntegrationsHelper $integrationsHelper */
        $this->integrationsHelper = $this->get('mautic.integrations.helper.config_integrations');

        try {
            $this->integrationObject        = $this->integrationsHelper->getIntegration($integration);
            $this->integrationConfiguration = $this->integrationObject->getIntegrationConfiguration();
        } catch (IntegrationNotFoundException $exception) {
            return $this->notFound();
        }

        $dispatcher = $this->get('event_dispatcher');
        $event      = new FormLoadEvent($this->integrationConfiguration);
        $dispatcher->dispatch(IntegrationEvents::INTEGRATION_CONFIG_FORM_LOAD, $event);

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
        if ($this->isFormCancelled($this->form)) {
            return $this->closeForm();
        }

        // Get the fields before the form binds partial data due to pagination
        $settings      = $this->integrationConfiguration->getFeatureSettings();
        $fieldMappings = $settings['sync']['fieldMappings'] ?? [];

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

            $settings['sync']['fieldMappings'] = $fieldMerger->getFieldMappings();

            /** @var FieldValidationHelper $fieldValidator */
            $fieldValidator = $this->get('mautic.integrations.helper.field_validator');
            $fieldValidator->validateRequiredFields($this->form, $this->integrationObject, $settings['sync']['fieldMappings']);

            $this->integrationConfiguration->setFeatureSettings($settings);
        }

        // Dispatch event prior to saving the Integration. Bundles/plugins may need to modify some field values before save
        /** @var EventDispatcherInterface $eventDispatcher */
        $eventDispatcher = $this->get('event_dispatcher');
        $configEvent     = new ConfigSaveEvent($this->integrationConfiguration);
        $eventDispatcher->dispatch(IntegrationEvents::INTEGRATION_CONFIG_BEFORE_SAVE, $configEvent);

        // Show the form if there are errors
        if (!$this->form->isValid() && (!$this->integrationObject instanceof ConfigFormAuthorizeButtonInterface || $this->integrationObject->isAuthorized())) {
            // Invalid form data
            // Integration IS NOT instance of ConfigFormAuthorizeButtonInterface
            // Integration IS instance of ConfigFormAuthorizeButtonInterface and IS authorized
            return $this->showForm();
        }

        // Save the integration configuration
        $this->integrationsHelper->saveIntegrationConfiguration($this->integrationConfiguration);

        // Dispatch after save event
        $eventDispatcher->dispatch(IntegrationEvents::INTEGRATION_CONFIG_AFTER_SAVE, $configEvent);

        // Show the form if the apply button was clicked
        if ($this->isFormApplied($this->form)) {
            // Regenerate the form
            $this->resetFieldsInSession();
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
            $this->integrationObject->getConfigFormName() ?: IntegrationConfigType::class,
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
        $integrationObject = $this->integrationObject;
        $form              = $this->setFormTheme($this->form, 'IntegrationsBundle:Config:form.html.php');
        $formHelper        = $this->get('templating.helper.form');

        $showFeaturesTab =
            $integrationObject instanceof ConfigFormFeaturesInterface ||
            $integrationObject instanceof ConfigFormSyncInterface ||
            $integrationObject instanceof ConfigFormFeatureSettingsInterface;

        $hasFeatureErrors = (
                $integrationObject instanceof ConfigFormFeatureSettingsInterface &&
                $formHelper->containsErrors($form['featureSettings']['integration'])
            ) || (
                isset($form['featureSettings']['sync']['integration']) &&
                $formHelper->containsErrors($form['featureSettings']['sync']['integration'])
            );

        $hasAuthErrors = $integrationObject instanceof ConfigFormAuthInterface && $formHelper->containsErrors($form['apiKeys']);

        $useSyncFeatures = $integrationObject instanceof ConfigFormSyncInterface;

        $useFeatureSettings = $integrationObject instanceof ConfigFormFeatureSettingsInterface;

        $useAuthorizationUrl = $integrationObject instanceof ConfigFormAuthorizeButtonInterface;

        $callbackUrl = $integrationObject instanceof ConfigFormCallbackInterface ?
            $integrationObject->getRedirectUri()
            : false;

        return $this->delegateView(
            [
                'viewParameters'  => [
                    'integrationObject'   => $integrationObject,
                    'form'                => $form,
                    'activeTab'           => $this->request->get('activeTab'),
                    'showFeaturesTab'     => $showFeaturesTab,
                    'hasFeatureErrors'    => $hasFeatureErrors,
                    'hasAuthErrors'       => $hasAuthErrors,
                    'useSyncFeatures'     => $useSyncFeatures,
                    'useFeatureSettings'  => $useFeatureSettings,
                    'useAuthorizationUrl' => $useAuthorizationUrl,
                    'callbackUrl'         => $callbackUrl,
                ],
                'contentTemplate' => $integrationObject->getConfigFormContentTemplate()
                    ?: 'IntegrationsBundle:Config:form.html.php',
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
        $this->resetFieldsInSession();

        $response = [
            'closeModal'    => 1,
            'enabled'       => $this->integrationConfiguration->getIsPublished(),
            'name'          => $this->integrationConfiguration->getName(),
            'mauticContent' => 'integrationsConfig',
        ];

        if ($this->integrationObject instanceof ConfigFormAuthorizeButtonInterface) {
            $response['authUrl'] = $this->integrationObject->getAuthorizationUrl();
        }

        return new JsonResponse($response);
    }

    private function resetFieldsInSession(): void
    {
        /** @var Session $session */
        $session = $this->get('session');
        $session->remove("{$this->integrationObject->getName()}-fields");
    }
}
