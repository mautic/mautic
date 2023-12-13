<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Controller;

use Mautic\CoreBundle\Controller\AbstractFormController;
use Mautic\CoreBundle\Twig\Extension\FormExtension;
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
use Mautic\IntegrationsBundle\Integration\Interfaces\ConfigFormNotesInterface;
use Mautic\IntegrationsBundle\Integration\Interfaces\ConfigFormSyncInterface;
use Mautic\IntegrationsBundle\IntegrationEvents;
use Mautic\PluginBundle\Entity\Integration;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;

class ConfigController extends AbstractFormController
{
    /**
     * @var BasicIntegration|ConfigFormInterface
     */
    private $integrationObject;

    /**
     * @var Integration
     */
    private $integrationConfiguration;

    /**
     * @return array|JsonResponse|RedirectResponse|Response
     */
    public function editAction(
        Request $request,
        ConfigIntegrationsHelper $integrationsHelper,
        EventDispatcherInterface $dispatcher,
        FieldValidationHelper $fieldValidator,
        FormFactoryInterface $formFactory,
        FormExtension $formExtension,
        string $integration
    ) {
        // Check ACL
        if (!$this->security->isGranted('plugin:plugins:manage')) {
            return $this->accessDenied();
        }

        try {
            $this->integrationObject        = $integrationsHelper->getIntegration($integration);
            $this->integrationConfiguration = $this->integrationObject->getIntegrationConfiguration();
        } catch (IntegrationNotFoundException) {
            return $this->notFound();
        }

        $event = new FormLoadEvent($this->integrationConfiguration);
        $dispatcher->dispatch($event, IntegrationEvents::INTEGRATION_CONFIG_FORM_LOAD);

        // Create the form
        $form = $this->getForm($formFactory);

        if (Request::METHOD_POST === $request->getMethod()) {
            return $this->submitForm($request, $integrationsHelper, $fieldValidator, $dispatcher, $formFactory, $formExtension, $form);
        }

        // Clear the session of previously stored fields in case it got stuck
        /** @var Session $session */
        $session = $request->getSession();
        $session->remove("$integration-fields");

        return $this->showForm($request, $form, $formExtension);
    }

    /**
     * @param FormInterface<FormInterface> $form
     */
    private function submitForm(
        Request $request,
        ConfigIntegrationsHelper $integrationsHelper,
        FieldValidationHelper $fieldValidator,
        EventDispatcherInterface $eventDispatcher,
        FormFactoryInterface $formFactory,
        FormExtension $formExtension,
        FormInterface $form
    ): JsonResponse|Response {
        if ($this->isFormCancelled($form)) {
            return $this->closeForm($request);
        }

        // Get the fields before the form binds partial data due to pagination
        $settings      = $this->integrationConfiguration->getFeatureSettings();
        $fieldMappings = $settings['sync']['fieldMappings'] ?? [];

        // Submit the form
        $form->handleRequest($request);
        if ($this->integrationObject instanceof ConfigFormSyncInterface) {
            $integration   = $this->integrationObject->getName();
            $settings      = $this->integrationConfiguration->getFeatureSettings();
            $session       = $request->getSession();
            $updatedFields = $session->get("$integration-fields", []);

            $fieldMerger = new FieldMergerHelper($this->integrationObject, $fieldMappings);

            foreach ($updatedFields as $object => $fields) {
                $fieldMerger->mergeSyncFieldMapping($object, $fields);
            }

            $settings['sync']['fieldMappings'] = $fieldMerger->getFieldMappings();

            $fieldValidator->validateRequiredFields($form, $this->integrationObject, $settings['sync']['fieldMappings']);

            $this->integrationConfiguration->setFeatureSettings($settings);
        }

        // Dispatch event prior to saving the Integration. Bundles/plugins may need to modify some field values before save
        $configEvent = new ConfigSaveEvent($this->integrationConfiguration);
        $eventDispatcher->dispatch($configEvent, IntegrationEvents::INTEGRATION_CONFIG_BEFORE_SAVE);

        // Show the form if there are errors and the plugin is published or the authorized button was clicked
        $integrationDetailsPost = $request->request->get('integration_details') ?? [];
        $authorize              = !empty($integrationDetailsPost['in_auth']);
        if ($form->isSubmitted() && !$form->isValid() && ($this->integrationConfiguration->getIsPublished() || $authorize)) {
            return $this->showForm($request, $form, $formExtension);
        }

        // Save the integration configuration
        $integrationsHelper->saveIntegrationConfiguration($this->integrationConfiguration);

        // Dispatch after save event
        $eventDispatcher->dispatch($configEvent, IntegrationEvents::INTEGRATION_CONFIG_AFTER_SAVE);

        // Show the form if the apply button was clicked
        if ($this->isFormApplied($form)) {
            // Regenerate the form
            $this->resetFieldsInSession($request);
            $form = $this->getForm($formFactory);

            return $this->showForm($request, $form, $formExtension);
        }

        // Otherwise close the modal
        return $this->closeForm($request);
    }

    /**
     * @return FormInterface<FormInterface>
     */
    private function getForm(FormFactoryInterface $formFactory)
    {
        return $formFactory->create(
            $this->integrationObject->getConfigFormName() ?: IntegrationConfigType::class,
            $this->integrationConfiguration,
            [
                'action'      => $this->generateUrl('mautic_integration_config', ['integration' => $this->integrationObject->getName()]),
                'integration' => $this->integrationObject->getName(),
            ]
        );
    }

    /**
     * @param FormInterface<FormInterface> $form
     */
    private function showForm(Request $request, FormInterface $form, FormExtension $formExtension): Response
    {
        $integrationObject = $this->integrationObject;
        $formView          = $form->createView();

        $showFeaturesTab = $integrationObject instanceof ConfigFormFeaturesInterface ||
            $integrationObject instanceof ConfigFormSyncInterface ||
            $integrationObject instanceof ConfigFormFeatureSettingsInterface;

        $hasFeatureErrors = (
            $integrationObject instanceof ConfigFormFeatureSettingsInterface &&
            $formExtension->containsErrors($formView['featureSettings']['integration'])
        ) || (
            isset($formView['featureSettings']['sync']['integration']) &&
            $formExtension->containsErrors($formView['featureSettings']['sync']['integration'])
        );

        $hasAuthErrors = $integrationObject instanceof ConfigFormAuthInterface && $formExtension->containsErrors($formView['apiKeys']);

        $useSyncFeatures = $integrationObject instanceof ConfigFormSyncInterface;

        $useFeatureSettings = $integrationObject instanceof ConfigFormFeatureSettingsInterface;

        $useAuthorizationUrl = $integrationObject instanceof ConfigFormAuthorizeButtonInterface;

        $callbackUrl = $integrationObject instanceof ConfigFormCallbackInterface ?
            $integrationObject->getRedirectUri()
            : false;

        $useConfigFormNotes = $integrationObject instanceof ConfigFormNotesInterface;

        return $this->delegateView(
            [
                'viewParameters' => [
                    'integrationObject'   => $integrationObject,
                    'form'                => $formView,
                    'activeTab'           => $request->get('activeTab'),
                    'showFeaturesTab'     => $showFeaturesTab,
                    'hasFeatureErrors'    => $hasFeatureErrors,
                    'hasAuthErrors'       => $hasAuthErrors,
                    'useSyncFeatures'     => $useSyncFeatures,
                    'useFeatureSettings'  => $useFeatureSettings,
                    'useAuthorizationUrl' => $useAuthorizationUrl,
                    'callbackUrl'         => $callbackUrl,
                    'useConfigFormNotes'  => $useConfigFormNotes,
                ],
                'contentTemplate' => $integrationObject->getConfigFormContentTemplate()
                    ?: '@Integrations/Config/form.html.twig',
                'passthroughVars' => [
                    'activeLink'    => '#mautic_plugin_index',
                    'mauticContent' => 'integrationsConfig',
                    'route'         => false,
                ],
            ]
        );
    }

    private function closeForm(Request $request): JsonResponse
    {
        $this->resetFieldsInSession($request);

        $response = [
            'closeModal'    => 1,
            'enabled'       => $this->integrationConfiguration->getIsPublished(),
            'name'          => $this->integrationConfiguration->getName(),
            'mauticContent' => 'integrationsConfig',
            'flashes'       => $this->getFlashContent(),
        ];

        if ($this->integrationObject instanceof ConfigFormAuthorizeButtonInterface) {
            $response['authUrl'] = $this->integrationObject->getAuthorizationUrl();
        }

        return new JsonResponse($response);
    }

    private function resetFieldsInSession(Request $request): void
    {
        $session = $request->getSession();
        $session->remove("{$this->integrationObject->getName()}-fields");
    }
}
