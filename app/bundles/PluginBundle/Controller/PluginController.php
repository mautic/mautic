<?php

namespace Mautic\PluginBundle\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Mautic\CoreBundle\Controller\FormController;
use Mautic\CoreBundle\Helper\InputHelper;
use Mautic\PluginBundle\Event\PluginIntegrationAuthRedirectEvent;
use Mautic\PluginBundle\Event\PluginIntegrationEvent;
use Mautic\PluginBundle\Facade\ReloadFacade;
use Mautic\PluginBundle\Form\Type\DetailsType;
use Mautic\PluginBundle\Integration\AbstractIntegration;
use Mautic\PluginBundle\Model\PluginModel;
use Mautic\PluginBundle\PluginEvents;
use Psr\Log\LoggerInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class PluginController extends FormController
{
    /**
     * @return JsonResponse|Response
     */
    public function indexAction(Request $request)
    {
        if (!$this->security->isGranted('plugin:plugins:manage')) {
            return $this->accessDenied();
        }

        /** @var \Mautic\PluginBundle\Model\PluginModel $pluginModel */
        $pluginModel = $this->getModel('plugin');

        // List of plugins for filter and to show as a single integration
        $plugins = $pluginModel->getEntities(
            [
                'filter' => [
                    'force' => [
                        [
                            'column' => 'p.isMissing',
                            'expr'   => 'eq',
                            'value'  => 0,
                        ],
                    ],
                ],
                'hydration_mode' => 'hydrate_array',
            ]
        );

        $session      = $request->getSession();
        $pluginFilter = $request->get('plugin', $session->get('mautic.integrations.filter', ''));

        $session->set('mautic.integrations.filter', $pluginFilter);

        /** @var \Mautic\PluginBundle\Helper\IntegrationHelper $integrationHelper */
        $integrationHelper  = $this->factory->getHelper('integration');
        $integrationObjects = $integrationHelper->getIntegrationObjects(null, null, true);
        $integrations       = $foundPlugins       = [];

        foreach ($integrationObjects as $name => $object) {
            $settings = $object->getIntegrationSettings();
            $plugin   = $settings->getPlugin();
            $pluginId = $plugin ? $plugin->getId() : $name;
            if (isset($plugins[$pluginId]) || $pluginId === $name) {
                $integrations[$name] = [
                    'name'     => $object->getName(),
                    'display'  => $object->getDisplayName(),
                    'icon'     => $integrationHelper->getIconPath($object),
                    'enabled'  => $settings->isPublished(),
                    'plugin'   => $pluginId,
                    'isBundle' => false,
                ];
            }

            $foundPlugins[$pluginId] = true;
        }

        $nonIntegrationPlugins = array_diff_key($plugins, $foundPlugins);
        foreach ($nonIntegrationPlugins as $plugin) {
            $integrations[$plugin['name']] = [
                'name'        => $plugin['bundle'],
                'display'     => $plugin['name'],
                'icon'        => $integrationHelper->getIconPath($plugin),
                'enabled'     => true,
                'plugin'      => $plugin['id'],
                'description' => $plugin['description'],
                'isBundle'    => true,
            ];
        }

        // sort by name
        uksort(
            $integrations,
            fn ($a, $b): int => strnatcasecmp($a, $b)
        );

        $tmpl = $request->isXmlHttpRequest() ? $request->get('tmpl', 'index') : 'index';

        if (!empty($pluginFilter)) {
            foreach ($plugins as $plugin) {
                if ($plugin['id'] == $pluginFilter) {
                    $pluginName = $plugin['name'];
                    $pluginId   = $plugin['id'];
                    break;
                }
            }
        }

        return $this->delegateView(
            [
                'viewParameters' => [
                    'items'        => $integrations,
                    'tmpl'         => $tmpl,
                    'pluginFilter' => ($pluginFilter) ? ['id' => $pluginId, 'name' => $pluginName] : false,
                    'plugins'      => $plugins,
                ],
                'contentTemplate' => '@MauticPlugin/Integration/grid.html.twig',
                'passthroughVars' => [
                    'activeLink'    => '#mautic_plugin_index',
                    'mauticContent' => 'integration',
                    'route'         => $this->generateUrl('mautic_plugin_index'),
                ],
            ]
        );
    }

    /**
     * @param string $name
     *
     * @return JsonResponse|Response
     */
    public function configAction(Request $request, EntityManagerInterface $em, LoggerInterface $mauticLogger, $name, $activeTab = 'details-container', $page = 1)
    {
        if (!$this->security->isGranted('plugin:plugins:manage')) {
            return $this->accessDenied();
        }
        if (!empty($request->get('activeTab'))) {
            $activeTab = $request->get('activeTab');
        }

        $session   = $request->getSession();

        $integrationDetailsPost = $request->request->get('integration_details') ?? [];
        $authorize              = empty($integrationDetailsPost['in_auth']) ? false : true;

        /** @var \Mautic\PluginBundle\Helper\IntegrationHelper $integrationHelper */
        $integrationHelper = $this->factory->getHelper('integration');
        /** @var AbstractIntegration $integrationObject */
        $integrationObject = $integrationHelper->getIntegrationObject($name);

        // Verify that the requested integration exists
        if (empty($integrationObject)) {
            throw $this->createNotFoundException($this->translator->trans('mautic.core.url.error.404'));
        }

        $object = ('leadFieldsContainer' === $activeTab) ? 'lead' : 'company';
        $limit  = $this->coreParametersHelper->get('default_pagelimit');
        $start  = (1 === $page) ? 0 : (($page - 1) * $limit);
        if ($start < 0) {
            $start = 0;
        }
        $session->set('mautic.plugin.'.$name.'.'.$object.'.start', $start);
        $session->set('mautic.plugin.'.$name.'.'.$object.'.page', $page);

        /** @var PluginModel $pluginModel */
        $pluginModel   = $this->getModel('plugin');
        $leadFields    = $pluginModel->getLeadFields();
        $companyFields = $pluginModel->getCompanyFields();
        /** @var \Mautic\PluginBundle\Integration\AbstractIntegration $integrationObject */
        $entity = $integrationObject->getIntegrationSettings();

        $form = $this->createForm(
            DetailsType::class,
            $entity,
            [
                'integration'        => $entity->getName(),
                'lead_fields'        => $leadFields,
                'company_fields'     => $companyFields,
                'integration_object' => $integrationObject,
                'action'             => $this->generateUrl('mautic_plugin_config', ['name' => $name]),
            ]
        );

        if ('POST' == $request->getMethod()) {
            $valid = false;
            if (!$cancelled = $this->isFormCancelled($form)) {
                $currentKeys            = $integrationObject->getDecryptedApiKeys($entity);
                $currentFeatureSettings = $entity->getFeatureSettings();
                $valid                  = $this->isFormValid($form);

                if ($authorize || $valid) {
                    $integration = $entity->getName();

                    if (isset($form['apiKeys'])) {
                        $keys = $form['apiKeys']->getData();

                        // Prevent merged keys
                        $secretKeys = $integrationObject->getSecretKeys();
                        foreach ($secretKeys as $secretKey) {
                            if (empty($keys[$secretKey]) && !empty($currentKeys[$secretKey])) {
                                $keys[$secretKey] = $currentKeys[$secretKey];
                            }
                        }
                        $integrationObject->encryptAndSetApiKeys($keys, $entity);
                    }

                    if (!$authorize) {
                        $features = $entity->getSupportedFeatures();
                        if (in_array('public_profile', $features) || in_array('push_lead', $features)) {
                            // Ungroup the fields
                            $mauticLeadFields = [];
                            foreach ($leadFields as $groupFields) {
                                $mauticLeadFields = array_merge($mauticLeadFields, $groupFields);
                            }
                            $mauticCompanyFields = [];
                            foreach ($companyFields as $groupFields) {
                                $mauticCompanyFields = array_merge($mauticCompanyFields, $groupFields);
                            }

                            if ($missing = $integrationObject->cleanUpFields($entity, $mauticLeadFields, $mauticCompanyFields)) {
                                if ($entity->getIsPublished()) {
                                    // Only fail validation if the integration is enabled
                                    if (!empty($missing['leadFields'])) {
                                        $valid = false;

                                        $form->get('featureSettings')->get('leadFields')->addError(
                                            new FormError(
                                                $this->translator->trans('mautic.plugin.field.required_mapping_missing', [], 'validators')
                                            )
                                        );
                                    }

                                    if (!empty($missing['companyFields'])) {
                                        $valid = false;

                                        $form->get('featureSettings')->get('companyFields')->addError(
                                            new FormError(
                                                $this->translator->trans('mautic.plugin.field.required_mapping_missing', [], 'validators')
                                            )
                                        );
                                    }
                                }
                            }
                        }
                    } else {
                        // make sure they aren't overwritten because of API connection issues
                        $entity->setFeatureSettings($currentFeatureSettings);
                    }

                    if ($valid || $authorize) {
                        $dispatcher = $this->dispatcher;
                        $mauticLogger->info('Dispatching integration config save event.');
                        if ($dispatcher->hasListeners(PluginEvents::PLUGIN_ON_INTEGRATION_CONFIG_SAVE)) {
                            $mauticLogger->info('Event dispatcher has integration config save listeners.');
                            $event = new PluginIntegrationEvent($integrationObject);

                            $dispatcher->dispatch($event, PluginEvents::PLUGIN_ON_INTEGRATION_CONFIG_SAVE);

                            $entity = $event->getEntity();
                        }

                        $em->persist($entity);
                        $em->flush();
                    }

                    if ($authorize) {
                        // redirect to the oauth URL
                        /** @var \Mautic\PluginBundle\Integration\AbstractIntegration $integrationObject */
                        $event = $this->dispatcher->dispatch(
                            new PluginIntegrationAuthRedirectEvent(
                                $integrationObject,
                                $integrationObject->getAuthLoginUrl()
                            ),
                            PluginEvents::PLUGIN_ON_INTEGRATION_AUTH_REDIRECT
                        );
                        $oauthUrl = $event->getAuthUrl();

                        return new JsonResponse(
                            [
                                'integration'         => $integration,
                                'authUrl'             => $oauthUrl,
                                'authorize'           => 1,
                                'popupBlockerMessage' => $this->translator->trans('mautic.core.popupblocked'),
                            ]
                        );
                    }
                }
            }

            if (($cancelled || ($valid && !$this->isFormApplied($form))) && !$authorize) {
                // Close the modal and return back to the list view
                return new JsonResponse(
                    [
                        'closeModal'    => 1,
                        'enabled'       => $entity->getIsPublished(),
                        'name'          => $integrationObject->getName(),
                        'mauticContent' => 'integrationConfig',
                        'sidebar'       => $this->get('twig')->render('@MauticCore/LeftPanel/index.html.twig'),
                    ]
                );
            }
        }

        $template    = $integrationObject->getFormTemplate();
        $objectTheme = $integrationObject->getFormTheme();
        $themes      = [
            '@MauticPlugin/FormTheme/Integration/layout.html.twig',
        ];
        if (is_array($objectTheme)) {
            $themes = array_merge($themes, $objectTheme);
        } elseif (is_string($objectTheme)) {
            $themes[] = $objectTheme;
        }
        $themes = array_unique($themes);

        $formSettings = $integrationObject->getFormSettings();
        $callbackUrl  = !empty($formSettings['requires_callback']) ? $integrationObject->getAuthCallbackUrl() : '';

        $formNotes    = [];
        $noteSections = ['authorization', 'features', 'feature_settings', 'custom'];
        foreach ($noteSections as $section) {
            if ('custom' === $section) {
                $formNotes[$section] = $integrationObject->getFormNotes($section);
            } else {
                [$specialInstructions, $alertType] = $integrationObject->getFormNotes($section);

                if (!empty($specialInstructions)) {
                    $formNotes[$section] = [
                        'note' => $specialInstructions,
                        'type' => $alertType,
                    ];
                }
            }
        }

        return $this->delegateView(
            [
                'viewParameters' => [
                    'form'         => $form->createView(),
                    'description'  => $integrationObject->getDescription(),
                    'formSettings' => $formSettings,
                    'formNotes'    => $formNotes,
                    'callbackUrl'  => $callbackUrl,
                    'activeTab'    => $activeTab,
                    'formThemes'   => $themes,
                ],
                'contentTemplate' => $template,
                'passthroughVars' => [
                    'activeLink'    => '#mautic_plugin_index',
                    'mauticContent' => 'integrationConfig',
                    'route'         => false,
                    'sidebar'       => $this->get('twig')->render('@MauticCore/LeftPanel/index.html.twig'),
                ],
            ]
        );
    }

    /**
     * @return array|JsonResponse|RedirectResponse|Response
     */
    public function infoAction($name)
    {
        if (!$this->security->isGranted('plugin:plugins:manage')) {
            return $this->accessDenied();
        }

        /** @var \Mautic\PluginBundle\Model\PluginModel $pluginModel */
        $pluginModel = $this->getModel('plugin');

        $bundle = $pluginModel->getRepository()->findOneBy(
            [
                'bundle' => InputHelper::clean($name),
            ]
        );

        if (!$bundle) {
            return $this->accessDenied();
        }

        /** @var \Mautic\PluginBundle\Helper\IntegrationHelper $integrationHelper */
        $integrationHelper = $this->factory->getHelper('integration');

        $bundle->splitDescriptions();

        return $this->delegateView(
            [
                'viewParameters' => [
                    'bundle' => $bundle,
                    'icon'   => $integrationHelper->getIconPath($bundle),
                ],
                'contentTemplate' => '@MauticPlugin/Integration/info.html.twig',
                'passthroughVars' => [
                    'activeLink'    => '#mautic_plugin_index',
                    'mauticContent' => 'integration',
                    'route'         => false,
                ],
            ]
        );
    }

    /**
     * Scans the addon bundles directly and loads bundles which are not registered to the database.
     *
     * @return Response
     */
    public function reloadAction(Request $request, ReloadFacade $reloadFacade)
    {
        if (!$this->security->isGranted('plugin:plugins:manage')) {
            return $this->accessDenied();
        }

        $this->addFlashMessage(
            $reloadFacade->reloadPlugins()
        );

        $viewParameters = [
            'page' => $request->getSession()->get('mautic.plugin.page'),
        ];

        // Refresh the index contents
        return $this->postActionRedirect(
            [
                'returnUrl'       => $this->generateUrl('mautic_plugin_index', $viewParameters),
                'viewParameters'  => $viewParameters,
                'contentTemplate' => 'Mautic\PluginBundle\Controller\PluginController::indexAction',
                'passthroughVars' => [
                    'activeLink'    => '#mautic_plugin_index',
                    'mauticContent' => 'plugin',
                ],
            ]
        );
    }
}
