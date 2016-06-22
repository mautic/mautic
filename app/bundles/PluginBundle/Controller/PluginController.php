<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PluginBundle\Controller;

use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Controller\FormController;
use Mautic\CoreBundle\Helper\InputHelper;
use Mautic\PluginBundle\Entity\Plugin;
use Mautic\PluginBundle\Event\PluginIntegrationAuthRedirectEvent;
use Mautic\PluginBundle\PluginEvents;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Class PluginController
 */
class PluginController extends FormController
{
    /**
     * @return JsonResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function indexAction()
    {
        if (!$this->factory->getSecurity()->isGranted('plugin:plugins:manage')) {
            return $this->accessDenied();
        }

        /** @var \Mautic\PluginBundle\Model\PluginModel $pluginModel */
        $pluginModel = $this->getModel('plugin');

        // List of plugins for filter and to show as a single integration
        $plugins = $pluginModel->getEntities(
            array(
                'hydration_mode' => 'hydrate_array'
            )
        );

        $session      = $this->factory->getSession();
        $pluginFilter = $this->request->get('plugin', $session->get('mautic.integrations.filter', ''));

        $session->set('mautic.integrations.filter', $pluginFilter);

        /** @var \Mautic\PluginBundle\Helper\IntegrationHelper $integrationHelper */
        $integrationHelper  = $this->factory->getHelper('integration');
        $integrationObjects = $integrationHelper->getIntegrationObjects(null, null, true);
        $integrations       = $foundPlugins = array();

        foreach ($integrationObjects as $name => $object) {
            $settings            = $object->getIntegrationSettings();
            $integrations[$name] = array(
                'name'     => $object->getName(),
                'display'  => $object->getDisplayName(),
                'icon'     => $integrationHelper->getIconPath($object),
                'enabled'  => $settings->isPublished(),
                'plugin'   => $settings->getPlugin()->getId(),
                'isBundle' => false
            );

            $foundPlugins[$settings->getPlugin()->getId()] = true;
        }

        $nonIntegrationPlugins = array_diff_key($plugins, $foundPlugins);
        foreach ($nonIntegrationPlugins as $plugin) {
            $integrations[$plugin['name']] = array(
                'name'        => $plugin['bundle'],
                'display'     => $plugin['name'],
                'icon'        => $integrationHelper->getIconPath($plugin),
                'enabled'     => true,
                'plugin'      => $plugin['id'],
                'description' => $plugin['description'],
                'isBundle'    => true
            );
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
            array(
                'viewParameters'  => array(
                    'items'        => $integrations,
                    'tmpl'         => $tmpl,
                    'pluginFilter' => ($pluginFilter) ? array('id' => $pluginId, 'name' => $pluginName) : false,
                    'plugins'      => $plugins
                ),
                'contentTemplate' => 'MauticPluginBundle:Integration:grid.html.php',
                'passthroughVars' => array(
                    'activeLink'    => '#mautic_plugin_index',
                    'mauticContent' => 'integration',
                    'route'         => $this->generateUrl('mautic_plugin_index'),
                )
            )
        );
    }

    /**
     * @param string $name
     *
     * @return JsonResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function configAction($name)
    {
        if (!$this->factory->getSecurity()->isGranted('plugin:plugins:manage')) {
            return $this->accessDenied();
        }

        $authorize = $this->request->request->get('integration_details[in_auth]', false, true);

        /** @var \Mautic\PluginBundle\Helper\IntegrationHelper $integrationHelper */
        $integrationHelper = $this->factory->getHelper('integration');
        $integrationObject = $integrationHelper->getIntegrationObject($name);

        // Verify that the requested integration exists
        if (empty($integrationObject)) {
            throw $this->createNotFoundException($this->get('translator')->trans('mautic.core.url.error.404'));
        }

        $leadFields = $this->getModel('plugin')->getLeadFields();

        /** @var \Mautic\PluginBundle\Integration\AbstractIntegration $integrationObject */
        $entity = $integrationObject->getIntegrationSettings();

        $form = $this->createForm(
            'integration_details',
            $entity,
            array(
                'integration'        => $entity->getName(),
                'lead_fields'        => $leadFields,
                'integration_object' => $integrationObject,
                'action'             => $this->generateUrl('mautic_plugin_config', array('name' => $name))
            )
        );

        if ($this->request->getMethod() == 'POST') {
            $valid = false;
            if (!$cancelled = $this->isFormCancelled($form)) {
                $currentKeys            = $integrationObject->getDecryptedApiKeys($entity);
                $currentFeatureSettings = $entity->getFeatureSettings();

                if ($valid = $this->isFormValid($form)) {
                    $em          = $this->factory->getEntityManager();
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
                            $submittedFields = $this->request->request->get('integration_details[featureSettings][leadFields]', array(), true);
                            if (isset($featureSettings['leadFields'])) {
                                foreach ($featureSettings['leadFields'] as $f => $v) {
                                    if (empty($v) || !isset($submittedFields[$f])) {
                                        unset($featureSettings['leadFields'][$f]);
                                    }
                                }
                                $entity->setFeatureSettings($featureSettings);
                            }
                        }
                    } else {
                        //make sure they aren't overwritten because of API connection issues
                        $entity->setFeatureSettings($currentFeatureSettings);
                    }

                    $em->persist($entity);
                    $em->flush();

                    if ($authorize) {
                        //redirect to the oauth URL
                        /** @var \Mautic\PluginBundle\Integration\AbstractIntegration $integrationObject */
                        $event = $this->factory->getDispatcher()->dispatch(
                            PluginEvents::PLUGIN_ON_INTEGRATION_AUTH_REDIRECT,
                            new PluginIntegrationAuthRedirectEvent(
                                $integrationObject,
                                $integrationObject->getAuthLoginUrl()
                            )
                        );
                        $oauthUrl = $event->getAuthUrl();

                        return new JsonResponse(
                            array(
                                'integration'         => $integration,
                                'authUrl'             => $oauthUrl,
                                'authorize'           => 1,
                                'popupBlockerMessage' => $this->factory->getTranslator()->trans('mautic.integration.oauth.popupblocked')
                            )
                        );
                    }
                }
            }

            if (($cancelled || $valid) && !$authorize) {
                // Close the modal and return back to the list view
                return new JsonResponse(
                    array(
                        'closeModal'    => 1,
                        'enabled'       => $entity->getIsPublished(),
                        'name'          => $integrationObject->getName(),
                        'mauticContent' => 'integration',
                    )
                );
            }
        }

        $template    = $integrationObject->getFormTemplate();
        $objectTheme = $integrationObject->getFormTheme();
        $default     = 'MauticPluginBundle:FormTheme\Integration';
        $themes      = array($default);
        if (is_array($objectTheme)) {
            $themes = array_merge($themes, $objectTheme);
        } else if ($objectTheme !== $default) {
            $themes[] = $objectTheme;
        }

        $formSettings = $integrationObject->getFormSettings();
        $callbackUrl  = !empty($formSettings['requires_callback']) ? $integrationObject->getAuthCallbackUrl() : '';

        $formNotes    = array();
        $noteSections = array('authorization', 'features', 'feature_settings');
        foreach ($noteSections as $section) {
            list($specialInstructions, $alertType) = $integrationObject->getFormNotes($section);
            if (!empty($specialInstructions)) {
                $formNotes[$section] = array(
                    'note' => $specialInstructions,
                    'type' => $alertType
                );
            }
        }

        return $this->delegateView(
            array(
                'viewParameters'  => array(
                    'form'         => $this->setFormTheme($form, $template, $themes),
                    'description'  => $integrationObject->getDescription(),
                    'formSettings' => $formSettings,
                    'formNotes'    => $formNotes,
                    'callbackUrl'  => $callbackUrl
                ),
                'contentTemplate' => $template,
                'passthroughVars' => array(
                    'activeLink'    => '#mautic_plugin_index',
                    'mauticContent' => 'integration',
                    'route'         => false
                )
            )
        );
    }

    /**
     * @param $name
     *
     * @return array|JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function infoAction($name)
    {
        if (!$this->factory->getSecurity()->isGranted('plugin:plugins:manage')) {
            return $this->accessDenied();
        }

        /** @var \Mautic\PluginBundle\Model\PluginModel $pluginModel */
        $pluginModel = $this->getModel('plugin');

        $bundle = $pluginModel->getRepository()->findOneBy(
            array(
                'bundle' => InputHelper::clean($name)
            )
        );

        if (!$bundle) {
            return $this->accessDenied();
        }

        /** @var \Mautic\PluginBundle\Helper\IntegrationHelper $integrationHelper */
        $integrationHelper = $this->factory->getHelper('integration');

        return $this->delegateView(
            array(
                'viewParameters'  => array(
                    'bundle' => $bundle,
                    'icon'   => $integrationHelper->getIconPath($bundle),
                ),
                'contentTemplate' => 'MauticPluginBundle:Integration:info.html.php',
                'passthroughVars' => array(
                    'activeLink'    => '#mautic_plugin_index',
                    'mauticContent' => 'integration',
                    'route'         => false
                )
            )
        );
    }

    /**
     * Scans the addon bundles directly and loads bundles which are not registered to the database
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function reloadAction()
    {
        if (!$this->factory->getSecurity()->isGranted('plugin:plugins:manage')) {
            return $this->accessDenied();
        }

        /** @var \Mautic\PluginBundle\Model\PluginModel $model */
        $model   = $this->getModel('plugin');
        $plugins = $this->factory->getParameter('plugin.bundles');
        $added   = $disabled = $updated = 0;

        // Get the metadata for plugins for installation
        $em             = $this->factory->getEntityManager();
        $allMetadata    = $em->getMetadataFactory()->getAllMetadata();
        $pluginMetadata = $pluginInstalledSchemas = $currentPluginTables = array();

        $currentSchema = $em->getConnection()->getSchemaManager()->createSchema();

        // Get current metadata and currently installed Tables

        /** @var \Doctrine\ORM\Mapping\ClassMetadata $meta */
        foreach ($allMetadata as $meta) {
            $namespace = $meta->fullyQualifiedClassName('');

            if (strpos($namespace, 'MauticPlugin') !== false) {
                $bundleName = str_replace('\Entity\\', '', $namespace);
                if (!isset($pluginMetadata[$bundleName])) {
                    $pluginMetadata[$bundleName] = array();
                }
                $pluginMetadata[$bundleName][$meta->getName()] = $meta;

                $table = $meta->getTableName();

                if (!isset($currentPluginTables[$bundleName])) {
                    $currentPluginTables[$bundleName] = array();
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

        $persist = array();

        $installedPlugins = $model->getEntities(
            array(
                'index' => 'bundle'
            )
        );

        /**
         * @var string $bundle
         * @var Plugin $plugin
         */
        foreach ($installedPlugins as $bundle => $plugin) {
            $persistUpdate = false;
            if (!isset($plugins[$bundle])) {
                if (!$plugin->getIsMissing()) {
                    //files are no longer found
                    $plugin->setIsMissing(true);
                    $disabled++;
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
                        $updated++;

                        //call the update callback
                        $callback        = $plugins[$bundle]['bundleClass'];
                        $metadata        = (isset($pluginMetadata[$plugins[$bundle]['namespace']]))
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
            $added++;
            $entity = new Plugin();
            $entity->setBundle($plugin['bundle']);

            $file = $plugin['directory'].'/Config/config.php';

            //update details of the bundle
            if (file_exists($file)) {
                $details = include $file;

                if (isset($details['version'])) {
                    $entity->setVersion($details['version']);
                };

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
            $callback = $plugin['bundleClass'];
            $metadata = (isset($pluginMetadata[$plugin['namespace']])) ? $pluginMetadata[$plugin['namespace']] : null;
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
            array(
                '%added%'    => $added,
                '%disabled%' => $disabled,
                '%updated%'  => $updated
            )
        );

        $viewParameters = array(
            'page' => $this->factory->getSession()->get('mautic.plugin.page')
        );

        // Refresh the index contents
        return $this->postActionRedirect(
            array(
                'returnUrl'       => $this->generateUrl('mautic_plugin_index', $viewParameters),
                'viewParameters'  => $viewParameters,
                'contentTemplate' => 'MauticPluginBundle:Plugin:index',
                'passthroughVars' => array(
                    'activeLink'    => '#mautic_plugin_index',
                    'mauticContent' => 'plugin'
                )
            )
        );
    }
}
