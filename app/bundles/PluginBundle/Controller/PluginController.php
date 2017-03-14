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
use Mautic\PluginBundle\Entity\Plugin;
use Mautic\PluginBundle\Event\PluginIntegrationAuthRedirectEvent;
use Mautic\PluginBundle\Event\PluginIntegrationEvent;
use Mautic\PluginBundle\Model\PluginModel;
use Mautic\PluginBundle\PluginEvents;
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
            $pluginId = $settings->getPlugin()->getId();
            if (isset($plugins[$pluginId])) {
                $integrations[$name] = [
                    'name'     => $object->getName(),
                    'display'  => $object->getDisplayName(),
                    'icon'     => $integrationHelper->getIconPath($object),
                    'enabled'  => $settings->isPublished(),
                    'plugin'   => $settings->getPlugin()->getId(),
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

        $session = $this->get('session');
        $limit   = $session->get('mautic.lead.limit', $this->coreParametersHelper->getParameter('default_pagelimit'));
        $start   = ($page === 1) ? 0 : (($page - 1) * $limit);
        if ($start < 0) {
            $start = 0;
        }
        //set what page currently on so that we can return here after form submission/cancellation
        if ($activeTab == 'leadFieldsContainer') {
            $session->set('mautic.plugin.lead.start', $start);
            $session->set('mautic.plugin.lead.page', $page);
        }
        if ($activeTab == 'companyFieldsContainer') {
            $session->set('mautic.plugin.company.start', $start);
            $session->set('mautic.plugin.company.lead.page', $page);
        }

        $authorize = $this->request->request->get('integration_details[in_auth]', false, true);

        /** @var \Mautic\PluginBundle\Helper\IntegrationHelper $integrationHelper */
        $integrationHelper = $this->factory->getHelper('integration');
        $integrationObject = $integrationHelper->getIntegrationObject($name);

        // Verify that the requested integration exists
        if (empty($integrationObject)) {
            throw $this->createNotFoundException($this->get('translator')->trans('mautic.core.url.error.404'));
        }

        /** @var PluginModel $pluginModel */
        $pluginModel = $this->getModel('plugin');

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

                if ($valid = $this->isFormValid($form)) {
                    $em          = $this->get('doctrine.orm.entity_manager');
                    $integration = $entity->getName();
                    // Merge keys
                    $keys = $form['apiKeys']->getData();

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
                            //make sure now non-existent aren't saved
                            $featureSettings = $entity->getFeatureSettings();
                            $submittedFields = $this->request->get('integration_details[featureSettings][leadFields]', [], true);
                            if (!empty($submittedFields)) {
                                if (isset($currentFeatureSettings['leadFields'])) {
                                    $featureSettings['leadFields'] = $currentFeatureSettings['leadFields'];
                                } else {
                                    $featureSettings['leadFields'] = [];
                                }
                                if (isset($currentFeatureSettings['update_mautic'])) {
                                    $featureSettings['update_mautic'] = $currentFeatureSettings['update_mautic'];
                                } else {
                                    $featureSettings['update_mautic'] = [];
                                }
                            }
                            $submittedCompanyFields = $this->request->request->get('integration_details[featureSettings][companyFields]', [], true);

                            if (!empty($submittedCompanyFields)) {
                                if (isset($currentFeatureSettings['companyFields'])) {
                                    $featureSettings['companyFields'] = $currentFeatureSettings['companyFields'];
                                } else {
                                    $featureSettings['companyFields'] = [];
                                }
                                if (isset($currentFeatureSettings['update_mautic_company'])) {
                                    $featureSettings['update_mautic_company'] = $currentFeatureSettings['update_mautic_company'];
                                } else {
                                    $featureSettings['update_mautic_company'] = [];
                                }
                            }
                            $entity->setFeatureSettings($featureSettings);
                        }
                    } else {
                        //make sure they aren't overwritten because of API connection issues
                        $entity->setFeatureSettings($currentFeatureSettings);
                    }

                    $dispatcher = $this->get('event_dispatcher');
                    if ($dispatcher->hasListeners(PluginEvents::PLUGIN_ON_INTEGRATION_CONFIG_SAVE)) {
                        $dispatcher->dispatch(PluginEvents::PLUGIN_ON_INTEGRATION_CONFIG_SAVE, new PluginIntegrationEvent($integrationObject));
                    }

                    $em->persist($entity);
                    $em->flush();

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

            if (($cancelled || $valid) && !$authorize) {
                // Close the modal and return back to the list view
                return new JsonResponse(
                    [
                        'closeModal'    => 1,
                        'enabled'       => $entity->getIsPublished(),
                        'name'          => $integrationObject->getName(),
                        'mauticContent' => 'integrationConfig',
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
        $noteSections = ['authorization', 'features', 'feature_settings'];
        foreach ($noteSections as $section) {
            list($specialInstructions, $alertType) = $integrationObject->getFormNotes($section);
            if (!empty($specialInstructions)) {
                $formNotes[$section] = [
                    'note' => $specialInstructions,
                    'type' => $alertType,
                ];
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
