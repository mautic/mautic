<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ConfigBundle\Controller;

use Mautic\CoreBundle\Controller\FormController;
use Mautic\ConfigBundle\Event\ConfigEvent;
use Mautic\ConfigBundle\Event\ConfigBuilderEvent;
use Mautic\ConfigBundle\ConfigEvents;

/**
 * Class ConfigController
 */
class ConfigController extends FormController
{

    /**
     * Controller action for editing the application configuration
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function editAction()
    {
        //admin only allowed
        if (!$this->factory->getUser()->isAdmin()) {
            return $this->accessDenied();
        }

        $event = new ConfigBuilderEvent($this->container);
        $dispatcher = $this->get('event_dispatcher');
        $dispatcher->dispatch(ConfigEvents::CONFIG_ON_GENERATE, $event);
        $forms = $event->getForms();

        $params = $this->getBundleParams($forms);

        /* @type \Mautic\ConfigBundle\Model\ConfigModel $model */
        $model = $this->factory->getModel('config');

        // Create the form
        $action = $this->generateUrl('mautic_config_action', array('objectAction' => 'edit'));
        $form   = $model->createForm($params, $this->get('form.factory'), array('action' => $action));

        // Check for a submitted form and process it
        if ($this->request->getMethod() == 'POST') {
            if (!$cancelled = $this->isFormCancelled($form)) {
                /** @var \Mautic\InstallBundle\Configurator\Configurator $configurator */
                $configurator = $this->get('mautic.configurator');

                // Bind request to the form
                $post     = $this->request->request;
                $formData = $form->getData();
                $formValues = array();

                // Merge the values POSTed with the current data
                foreach ($formData as $bundle => $bundleConfig) {

                    $formValues[$bundle] = array();

                    foreach ($bundleConfig['parameters'] as $key => $value) {
                        $value = $post->get('config[' . $bundle . '][' . $key . ']', null, true);
                        $formValues[$bundle][$key] = $value;
                    }
                }

                // Dispatch pre-save event. Bundles may need to modify some field values like booleans and passwords before save
                $configEvent = new ConfigEvent($formValues, $post);
                $dispatcher->dispatch(ConfigEvents::CONFIG_PRE_SAVE, $configEvent);
                $formValues = $configEvent->getConfig();

                // Merge each bundle's updated configuration into the local configuration
                foreach ($formValues as $object) {
                    $configurator->mergeParameters($object);
                }

                try {
                    $configurator->write();

                    $this->request->getSession()->getFlashBag()->add(
                        'notice',
                        $this->get('translator')->trans('mautic.config.config.notice.updated', array(), 'flashes')
                    );

                    // We must clear the application cache for the updated values to take effect
                    $this->clearCacheFile();
                } catch (RuntimeException $exception) {
                    $this->request->getSession()->getFlashBag()->add(
                        'error',
                        $this->get('translator')->trans('mautic.config.config.error.not.updated', array(), 'flashes')
                    );
                }
            }

            // If the form is saved or cancelled, redirect back to the dashboard
            if ($cancelled || !$this->isFormApplied($form)) {
                return $this->redirect($this->generateUrl('mautic_dashboard_index'));
            }

            // To get here, the form must have been applied; we must redirect to $this in order to force a proper refresh
            return $this->redirect($this->generateUrl('mautic_config_action', array('objectAction' => 'edit')));
        }

        $tmpl = $this->request->isXmlHttpRequest() ? $this->request->get('tmpl', 'index') : 'index';

        return $this->delegateView(array(
            'viewParameters'  =>  array(
                'params'      => $params,
                'tmpl'        => $tmpl,
                'security'    => $this->factory->getSecurity(),
                'form'        => $form->createView()
            ),
            'contentTemplate' => 'MauticConfigBundle:Config:form.html.php',
            'passthroughVars' => array(
                'activeLink'     => '#mautic_config_index',
                'mauticContent'  => 'config',
                'route'          => $this->generateUrl('mautic_config_action', array('objectAction' => 'edit'))
            )
        ));
    }

    /**
     * Retrieves the parameters defined in each bundle and merges with the local params
     *
     * @param array $forms
     * 
     * @return array
     */
    private function getBundleParams($forms)
    {
        $doNotChange = $this->container->getParameter('mautic.security.restrictedConfigFields');

        // Import the current local configuration, $parameters is defined in this file
        $localConfigFile = $this->factory->getLocalConfigFile();

        /** @var $parameters */
        include $localConfigFile;

        $localParams = $parameters;

        $params = array();
        $mauticBundles = $this->factory->getParameter('bundles');
        $addonBundles  = $this->factory->getParameter('addon.bundles');

        foreach ($forms as &$form) {
            $parameters = $form['parameters'];

            // Merge the bundle params with the local params
            foreach ($parameters as $key => $value) {
                if (in_array($key, $doNotChange)) {
                    unset($parameters[$key]);
                }
                elseif (array_key_exists($key, $localParams)) {
                    $parameters[$key] = $localParams[$key];
                }
            }

            $form['parameters'] = $parameters;
            $params[$form['bundle']] = $form;
        }

        // Remove code below after all bundles use events above

        foreach ($mauticBundles as $bundle) {
            if (!isset($params[$bundle['bundle']])) {
                // Build the path to the bundle configuration
                $paramsFile = $bundle['directory'] . '/Config/parameters.php';

                if (file_exists($paramsFile)) {
                    // Import the bundle configuration, $parameters is defined in this file
                    include $paramsFile;

                    // Merge the bundle params with the local params
                    foreach ($parameters as $key => $value) {
                        if (in_array($key, $doNotChange)) {
                            unset($parameters[$key]);
                        }
                        elseif (array_key_exists($key, $localParams)) {
                            $parameters[$key] = $localParams[$key];
                        }
                    }

                    $params[$bundle['bundle']] = $parameters;
                }
            }
        }

        foreach ($addonBundles as $bundle) {
            // Build the path to the bundle configuration
            $paramsFile = $bundle['directory'] . '/Config/parameters.php';

            if (file_exists($paramsFile)) {
                // Import the bundle configuration, $parameters is defined in this file
                include $paramsFile;

                // Merge the bundle params with the local params
                foreach ($parameters as $key => $value) {
                    if (in_array($key, $doNotChange)) {
                        unset($parameters[$key]);
                    }
                    elseif (array_key_exists($key, $localParams)) {
                        $parameters[$key] = $localParams[$key];
                    }
                }

                $params[$bundle['bundle']] = $parameters;
            }
        }

        return $params;
    }
}
