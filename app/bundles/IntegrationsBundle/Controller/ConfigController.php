<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Controller;

use Mautic\CoreBundle\Controller\AbstractFormController;
use Mautic\CoreBundle\Twig\Extension\FormExtension;
use Mautic\IntegrationsBundle\Event\ConfigAuthUrlEvent;
use Mautic\IntegrationsBundle\Event\ConfigSaveEvent;
use Mautic\IntegrationsBundle\Event\FormLoadEvent;
use Mautic\IntegrationsBundle\Event\KeysSaveEvent;
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
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class ConfigController extends AbstractFormController
{
    /**
     * @var BasicIntegration|ConfigFormInterface
     */
    private $integrationObject;

    /**
     * @var Integration
     */
    private $integrationConfiguration;
    private ConfigIntegrationsHelper $integrationsHelper;
    private FieldValidationHelper $fieldValidator;
    private FormFactoryInterface $formFactory;
    private FormExtension $formExtension;

    public function editAction(
        Request $request,
        string $integration,
    ): Response {
        // Check ACL
        if (!$this->security->isGranted('plugin:plugins:manage')) {
            $this->throwAccessDenied();
        }

        try {
            $this->integrationObject        = $this->integrationsHelper->getIntegration($integration);
            $this->integrationConfiguration = $this->integrationObject->getIntegrationConfiguration();
        } catch (IntegrationNotFoundException) {
            return $this->notFound();
        }

        $event = new FormLoadEvent($this->integrationConfiguration);
        $this->dispatcher->dispatch($event, IntegrationEvents::INTEGRATION_CONFIG_FORM_LOAD);

        // Create the form
        $form = $this->getForm();

        if (Request::METHOD_POST === $request->getMethod()) {
            return $this->submitForm($request, $this->dispatcher, $form);
        }

        // Clear the session of previously stored fields in case it got stuck
        $session = $request->getSession();
        $session->remove("{$integration}-fields");

        return $this->showForm($request, $form);
    }

    /**
     * @param FormInterface<mixed> $form
     */
    private function submitForm(
        Request $request,
        EventDispatcherInterface $eventDispatcher,
        FormInterface $form,
    ): JsonResponse|Response {
        if ($this->isFormCancelled($form)) {
            return $this->closeForm($request);
        }

        // Get the fields before the form binds partial data due to pagination
        $settings          = $this->integrationConfiguration->getFeatureSettings();
        $fieldMappings     = $settings['sync']['fieldMappings'] ?? [];
        $oldApiKeys        = $this->integrationConfiguration->getApiKeys();

        // Submit the form
        $form->handleRequest($request);

        $configEvent = new KeysSaveEvent($this->integrationConfiguration, $oldApiKeys);
        $this->dispatcher->dispatch($configEvent, IntegrationEvents::INTEGRATION_API_KEYS_BEFORE_SAVE);

        if ($this->integrationObject instanceof ConfigFormSyncInterface) {
            $integration   = $this->integrationObject->getName();
            $settings      = $this->integrationConfiguration->getFeatureSettings();
            $session       = $request->getSession();
            $updatedFields = $session->get("{$integration}-fields", []);

            $fieldMerger = new FieldMergerHelper($this->integrationObject, $fieldMappings);

            foreach ($updatedFields as $object => $fields) {
                $fieldMerger->mergeSyncFieldMapping($object, $fields);
            }

            $settings['sync']['fieldMappings'] = $fieldMerger->getFieldMappings();

            $this->fieldValidator->validateRequiredFields($form, $this->integrationObject, $settings['sync']['fieldMappings']);

            $this->integrationConfiguration->setFeatureSettings($settings);
        }

        // Dispatch event prior to saving the Integration. Bundles/plugins may need to modify some field values before save
        $configEvent = new ConfigSaveEvent($this->integrationConfiguration);
        $eventDispatcher->dispatch($configEvent, IntegrationEvents::INTEGRATION_CONFIG_BEFORE_SAVE);

        // Show the form if there are errors and the plugin is published or the authorized button was clicked
        $integrationDetailsPost = $request->request->all()['integration_details'] ?? [];
        $authorize              = !empty($integrationDetailsPost['in_auth']);
        if ($form->isSubmitted() && !$form->isValid() && ($this->integrationConfiguration->getIsPublished() || $authorize)) {
            return $this->showForm($request, $form);
        }

        // Save the integration configuration
        $this->integrationsHelper->saveIntegrationConfiguration($this->integrationConfiguration);

        // Dispatch after save event
        $eventDispatcher->dispatch($configEvent, IntegrationEvents::INTEGRATION_CONFIG_AFTER_SAVE);

        // Show the form if the apply button was clicked
        if ($this->isFormApplied($form)) {
            // Regenerate the form
            $this->resetFieldsInSession($request);
            $form = $this->getForm();

            return $this->showForm($request, $form);
        }

        // Otherwise close the modal
        return $this->closeForm($request);
    }

    /**
     * @return FormInterface<mixed>
     */
    private function getForm(): FormInterface
    {
        return $this->formFactory->create(
            $this->integrationObject->getConfigFormName() ?: IntegrationConfigType::class,
            $this->integrationConfiguration,
            [
                'action'      => $this->generateUrl('mautic_integration_config', ['integration' => $this->integrationObject->getName()]),
                'integration' => $this->integrationObject->getName(),
            ]
        );
    }

    /**
     * @param FormInterface<mixed> $form
     */
    private function showForm(Request $request, FormInterface $form): Response
    {
        $formView = $form->createView();

        $showFeaturesTab = $this->integrationObject instanceof ConfigFormFeaturesInterface
            || $this->integrationObject instanceof ConfigFormSyncInterface
            || $this->integrationObject instanceof ConfigFormFeatureSettingsInterface;

        $hasFeatureErrors = (
            $this->integrationObject instanceof ConfigFormFeatureSettingsInterface
            && $this->formExtension->containsErrors($formView['featureSettings']['integration'])
        ) || (
            isset($formView['featureSettings']['sync']['integration'])
            && $this->formExtension->containsErrors($formView['featureSettings']['sync']['integration'])
        );

        $hasAuthErrors = $this->integrationObject instanceof ConfigFormAuthInterface && $this->formExtension->containsErrors($formView['apiKeys']);

        $useSyncFeatures = $this->integrationObject instanceof ConfigFormSyncInterface;

        $useFeatureSettings = $this->integrationObject instanceof ConfigFormFeatureSettingsInterface;

        $useAuthorizationUrl = $this->integrationObject instanceof ConfigFormAuthorizeButtonInterface;

        $callbackUrl = $this->integrationObject instanceof ConfigFormCallbackInterface ?
            $this->integrationObject->getRedirectUri()
            : false;

        $useConfigFormNotes = $this->integrationObject instanceof ConfigFormNotesInterface;

        $plugin  = $this->integrationObject->getIntegrationSettings()->getPlugin();
        $version = $plugin?->getVersion();

        return $this->delegateView(
            [
                'viewParameters' => [
                    'integrationObject'   => $this->integrationObject,
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
                'contentTemplate' => $this->integrationObject->getConfigFormContentTemplate()
                    ?: '@Integrations/Config/form.html.twig',
                'passthroughVars' => [
                    'activeLink'    => '#mautic_plugin_index',
                    'mauticContent' => 'integrationsConfig',
                    'route'         => false,
                    'pluginVersion' => $version,
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
            // Dispatch event to allow listeners to extract information and/or manipulate the URL
            $authUrl      = $this->integrationObject->getAuthorizationUrl();
            $authUrlEvent = new ConfigAuthUrlEvent($this->integrationConfiguration, $authUrl);
            $this->dispatcher->dispatch($authUrlEvent, IntegrationEvents::INTEGRATION_CONFIG_ON_GENERATE_AUTH_URL);

            $response['authUrl'] = $authUrlEvent->getAuthUrl();
        }

        return new JsonResponse($response);
    }

    private function resetFieldsInSession(Request $request): void
    {
        $session = $request->getSession();
        $session->remove("{$this->integrationObject->getName()}-fields");
    }

    #[\Symfony\Contracts\Service\Attribute\Required]
    public function autowireConfigController(
        ConfigIntegrationsHelper $integrationsHelper,
        FieldValidationHelper $fieldValidator,
        FormFactoryInterface $formFactory,
        FormExtension $formExtension,
    ): void {
        $this->integrationsHelper = $integrationsHelper;
        $this->fieldValidator = $fieldValidator;
        $this->formFactory = $formFactory;
        $this->formExtension = $formExtension;
    }
}
