<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PluginBundle\Controller;

use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Controller\FormController;
use Mautic\CoreBundle\Helper\InputHelper;
use Mautic\PluginBundle\Entity\Integration;
use Mautic\PluginBundle\Entity\Plugin;
use Mautic\PluginBundle\Event\PluginIntegrationAuthRedirectEvent;
use Mautic\PluginBundle\Event\PluginIntegrationEvent;
use Mautic\PluginBundle\Integration\AbstractIntegration;
use Mautic\PluginBundle\Model\PluginModel;
use Mautic\PluginBundle\PluginEvents;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class PluginController.
 */
class PluginController extends FormController
{
    /**
     * @return JsonResponse|Response
     */
    public function indexAction()
    {
        if (!$this->get('mautic.security')->isGranted('plugin:plugins:manage')) {
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

        $session      = $this->get('session');
        $pluginFilter = $this->request->get('plugin', $session->get('mautic.integrations.filter', ''));

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

        //sort by name
        uksort(
            $integrations,
            function ($a, $b) {
                return strnatcasecmp($a, $b);
            }
        );

        $tmpl = $this->request->isXmlHttpRequest() ? $this->request->get('tmpl', 'index') : 'index';

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
                'contentTemplate' => 'MauticPluginBundle:Integration:grid.html.php',
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
    public function configAction($name, $activeTab = 'details-container', $page = 1)
    {
        if (!$this->get('mautic.security')->isGranted('plugin:plugins:manage')) {
            return $this->accessDenied();
        }
        if (!empty($this->request->get('activeTab'))) {
            $activeTab = $this->request->get('activeTab');
        }

        $session   = $this->get('session');
        $authorize = $this->request->request->get('integration_details[in_auth]', false, true);

        /** @var \Mautic\PluginBundle\Helper\IntegrationHelper $integrationHelper */
        $integrationHelper = $this->factory->getHelper('integration');
        /** @var AbstractIntegration $integrationObject */
        $integrationObject = $integrationHelper->getIntegrationObject($name);

        // Verify that the requested integration exists
        if (empty($integrationObject)) {
            throw $this->createNotFoundException($this->get('translator')->trans('mautic.core.url.error.404'));
        }

        $object = ('leadFieldsContainer' === $activeTab) ? 'lead' : 'company';
        $limit  = $this->coreParametersHelper->getParameter('default_pagelimit');
        $start  = ($page === 1) ? 0 : (($page - 1) * $limit);
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
            'integration_details',
            $entity,
            [
                'integration'        => $entity->getName(),
                'lead_fields'        => $leadFields,
                'company_fields'     => $companyFields,
                'integration_object' => $integrationObject,
                'action'             => $this->generateUrl('mautic_plugin_config', ['name' => $name]),
            ]
        );

        if ($this->request->getMethod() == 'POST') {
            $valid = false;
            if (!$cancelled = $this->isFormCancelled($form)) {
                $currentKeys            = $integrationObject->getDecryptedApiKeys($entity);
                $currentFeatureSettings = $entity->getFeatureSettings();
                $valid                  = $this->isFormValid($form);

                if ($authorize || $valid) {
                    $em          = $this->get('doctrine.orm.entity_manager');
                    $integration = $entity->getName();
                    $keys        = $form['apiKeys']->getData();

                    // Prevent merged keys
                    $secretKeys = $integrationObject->getSecretKeys();
                    foreach ($secretKeys as $secretKey) {
                        if (empty($keys[$secretKey]) && !empty($currentKeys[$secretKey])) {
                            $keys[$secretKey] = $currentKeys[$secretKey];
                        }
                    }
                    $integrationObject->encryptAndSetApiKeys($keys, $entity);

                    if (!$authorize) {
                        $features = $entity->getSupportedFeatures();
                        if (in_array('public_profile', $features) || in_array('push_lead', $features)) {
                            // Ungroup the fields
                            $mauticLeadFields = [];
                            foreach ($leadFields as $group => $groupFields) {
                                $mauticLeadFields = array_merge($mauticLeadFields, $groupFields);
                            }
                            $mauticCompanyFields = [];
                            foreach ($companyFields as $group => $groupFields) {
                                $mauticCompanyFields = array_merge($mauticCompanyFields, $groupFields);
                            }

                            if ($missing = $integrationObject->cleanUpFields($entity, $mauticLeadFields, $mauticCompanyFields)) {
                                if ($entity->getIsPublished()) {
                                    // Only fail validation if the integration is enabled
                                    if (!empty($missing['leadFields'])) {
                                        $valid = false;

                                        $form->get('featureSettings')->get('leadFields')->addError(
                                            new FormError(
                                                $this->get('translator')->trans('mautic.plugin.field.required_mapping_missing', [], 'validators')
                                            )
                                        );
                                    }

                                    if (!empty($missing['companyFields'])) {
                                        $valid = false;

                                        $form->get('featureSettings')->get('companyFields')->addError(
                                            new FormError(
                                                $this->get('translator')->trans('mautic.plugin.field.required_mapping_missing', [], 'validators')
                                            )
                                        );
                                    }
                                }
                            }
                        }
                    } else {
                        //make sure they aren't overwritten because of API connection issues
                        $entity->setFeatureSettings($currentFeatureSettings);
                    }

                    if ($valid || $authorize) {
                        $dispatcher = $this->get('event_dispatcher');
                        $this->get('logger')->info('Dispatching integration config save event.');
                        if ($dispatcher->hasListeners(PluginEvents::PLUGIN_ON_INTEGRATION_CONFIG_SAVE)) {
                            $this->get('logger')->info('Event dispatcher has integration config save listeners.');
                            $event = new PluginIntegrationEvent($integrationObject);

                            $dispatcher->dispatch(PluginEvents::PLUGIN_ON_INTEGRATION_CONFIG_SAVE, $event);

                            $entity = $event->getEntity();
                        }

                        $em->persist($entity);
                        $em->flush();
                    }

                    if ($authorize) {
                        //redirect to the oauth URL
                        /** @var \Mautic\PluginBundle\Integration\AbstractIntegration $integrationObject */
                        $event = $this->dispatcher->dispatch(
                            PluginEvents::PLUGIN_ON_INTEGRATION_AUTH_REDIRECT,
                            new PluginIntegrationAuthRedirectEvent(
                                $integrationObject,
                                $integrationObject->getAuthLoginUrl()
                            )
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
                        'sidebar'       => $this->get('templating')->render('MauticCoreBundle:LeftPanel:index.html.php'),
                    ]
                );
            }
        }

        $template    = $integrationObject->getFormTemplate();
        $objectTheme = $integrationObject->getFormTheme();
        $default     = 'MauticPluginBundle:FormTheme\Integration';
        $themes      = [$default];
        if (is_array($objectTheme)) {
            $themes = array_merge($themes, $objectTheme);
        } elseif ($objectTheme !== $default) {
            $themes[] = $objectTheme;
        }

        $formSettings = $integrationObject->getFormSettings();
        $callbackUrl  = !empty($formSettings['requires_callback']) ? $integrationObject->getAuthCallbackUrl() : '';

        $formNotes    = [];
        $noteSections = ['authorization', 'features', 'feature_settings', 'custom'];
        foreach ($noteSections as $section) {
            if ('custom' === $section) {
                $formNotes[$section] = $integrationObject->getFormNotes($section);
            } else {
                list($specialInstructions, $alertType) = $integrationObject->getFormNotes($section);

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
                    'form'         => $this->setFormTheme($form, $template, $themes),
                    'description'  => $integrationObject->getDescription(),
                    'formSettings' => $formSettings,
                    'formNotes'    => $formNotes,
                    'callbackUrl'  => $callbackUrl,
                    'activeTab'    => $activeTab,
                ],
                'contentTemplate' => $template,
                'passthroughVars' => [
                    'activeLink'    => '#mautic_plugin_index',
                    'mauticContent' => 'integrationConfig',
                    'route'         => false,
                    'sidebar'       => $this->get('templating')->render('MauticCoreBundle:LeftPanel:index.html.php'),
                ],
            ]
        );
    }

    /**
     * @param $name
     *
     * @return array|JsonResponse|RedirectResponse|Response
     */
    public function infoAction($name)
    {
        if (!$this->get('mautic.security')->isGranted('plugin:plugins:manage')) {
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

        return $this->delegateView(
            [
                'viewParameters' => [
                    'bundle' => $bundle,
                    'icon'   => $integrationHelper->getIconPath($bundle),
                ],
                'contentTemplate' => 'MauticPluginBundle:Integration:info.html.php',
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
     * @return JsonResponse
     */
    public function reloadAction()
    {
        if (!$this->get('mautic.security')->isGranted('plugin:plugins:manage')) {
            return $this->accessDenied();
        }

        /** @var \Mautic\PluginBundle\Model\PluginModel $model */
        $model   = $this->getModel('plugin');
        $plugins = $this->coreParametersHelper->getParameter('plugin.bundles');
        $added   = $disabled   = $updated   = 0;

        // Get the metadata for plugins for installation
        $em             = $this->get('doctrine.orm.entity_manager');
        $allMetadata    = $em->getMetadataFactory()->getAllMetadata();
        $pluginMetadata = $pluginInstalledSchemas = $currentPluginTables = [];

        $currentSchema = $em->getConnection()->getSchemaManager()->createSchema();

        // Get current metadata and currently installed Tables

        /** @var \Doctrine\ORM\Mapping\ClassMetadata $meta */
        foreach ($allMetadata as $meta) {
            $namespace = $meta->fullyQualifiedClassName('');

            if (strpos($namespace, 'MauticPlugin') !== false) {
                $bundleName = str_replace('\Entity\\', '', $namespace);
                if (!isset($pluginMetadata[$bundleName])) {
                    $pluginMetadata[$bundleName] = [];
                }
                $pluginMetadata[$bundleName][$meta->getName()] = $meta;

                $table = $meta->getTableName();

                if (!isset($currentPluginTables[$bundleName])) {
                    $currentPluginTables[$bundleName] = [];
                }

                if ($currentSchema->hasTable($table)) {
                    $currentPluginTables[$bundleName][] = $currentSchema->getTable($table);
                }
            }
        }

        // Create a Schema just for the plugin for updating
        foreach ($currentPluginTables as $bundleName => $tables) {
            $pluginInstalledSchemas[$bundleName] = new Schema($tables);
        }

        $persist = [];

        $installedPlugins = $model->getEntities(
            [
                'index' => 'bundle',
            ]
        );

        /**
         * @var string
         * @var Plugin $plugin
         */
        foreach ($installedPlugins as $bundle => $plugin) {
            $persistUpdate = false;
            if (!isset($plugins[$bundle])) {
                if (!$plugin->getIsMissing()) {
                    //files are no longer found
                    $plugin->setIsMissing(true);
                    $persistUpdate = true;
                    ++$disabled;
                }
            } else {
                if ($plugin->getIsMissing()) {
                    //was lost but now is found
                    $plugin->setIsMissing(false);
                    $persistUpdate = true;
                }

                $file = $plugins[$bundle]['directory'].'/Config/config.php';

                //update details of the bundle
                if (file_exists($file)) {
                    /** @var array $details */
                    $details = include $file;

                    //compare versions to see if an update is necessary
                    $version = isset($details['version']) ? $details['version'] : '';
                    if (!empty($version) && version_compare($plugin->getVersion(), $version) == -1) {
                        ++$updated;

                        //call the update callback
                        $callback = $plugins[$bundle]['bundleClass'];
                        $metadata = (isset($pluginMetadata[$plugins[$bundle]['namespace']]))
                            ? $pluginMetadata[$plugins[$bundle]['namespace']] : null;
                        $installedSchema = (isset($pluginInstalledSchemas[$plugins[$bundle]['namespace']]))
                            ? $pluginInstalledSchemas[$plugins[$bundle]['namespace']] : null;

                        $callback::onPluginUpdate($plugin, $this->factory, $metadata, $installedSchema);

                        unset($metadata, $installedSchema);

                        $persistUpdate = true;
                    }

                    $plugin->setVersion($version);

                    $plugin->setName(
                        isset($details['name']) ? $details['name'] : $plugins[$bundle]['base']
                    );

                    if (isset($details['description'])) {
                        $plugin->setDescription($details['description']);
                    }

                    if (isset($details['author'])) {
                        $plugin->setAuthor($details['author']);
                    }
                }

                unset($plugins[$bundle]);
            }
            if ($persistUpdate) {
                $persist[] = $plugin;
            }
        }

        //rest are new
        foreach ($plugins as $plugin) {
            ++$added;
            $entity = new Plugin();
            $entity->setBundle($plugin['bundle']);

            $file = $plugin['directory'].'/Config/config.php';

            //update details of the bundle
            if (file_exists($file)) {
                $details = include $file;

                if (isset($details['version'])) {
                    $entity->setVersion($details['version']);
                }

                $entity->setName(
                    isset($details['name']) ? $details['name'] : $plugin['base']
                );

                if (isset($details['description'])) {
                    $entity->setDescription($details['description']);
                }

                if (isset($details['author'])) {
                    $entity->setAuthor($details['author']);
                }
            }

            // Call the install callback
            $callback        = $plugin['bundleClass'];
            $metadata        = (isset($pluginMetadata[$plugin['namespace']])) ? $pluginMetadata[$plugin['namespace']] : null;
            $installedSchema = (isset($pluginInstalledSchemas[$plugin['namespace']]))
                ? $pluginInstalledSchemas[$plugin['namespace']] : null;

            $callback::onPluginInstall($entity, $this->factory, $metadata, $installedSchema);

            $persist[] = $entity;
        }

        if (!empty($persist)) {
            $model->saveEntities($persist);
        }

        // Alert the user to the number of additions
        $this->addFlash(
            'mautic.plugin.notice.reloaded',
            [
                '%added%'    => $added,
                '%disabled%' => $disabled,
                '%updated%'  => $updated,
            ]
        );

        $viewParameters = [
            'page' => $this->get('session')->get('mautic.plugin.page'),
        ];

        // Refresh the index contents
        return $this->postActionRedirect(
            [
                'returnUrl'       => $this->generateUrl('mautic_plugin_index', $viewParameters),
                'viewParameters'  => $viewParameters,
                'contentTemplate' => 'MauticPluginBundle:Plugin:index',
                'passthroughVars' => [
                    'activeLink'    => '#mautic_plugin_index',
                    'mauticContent' => 'plugin',
                ],
            ]
        );
    }
}
