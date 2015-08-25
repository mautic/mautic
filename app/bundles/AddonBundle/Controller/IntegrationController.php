<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\AddonBundle\Controller;

use Mautic\CoreBundle\Controller\FormController;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Class IntegrationController
 */
class IntegrationController extends FormController
{
    /**
     * @return JsonResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function indexAction ()
    {
        if (!$this->factory->getSecurity()->isGranted('addon:addons:manage')) {
            return $this->accessDenied();
        }

        $session     = $this->factory->getSession();
        $addonFilter = $this->request->get('addon', $session->get('mautic.integrations.filter', ''));
        $session->set('mautic.integrations.filter', $addonFilter);

        $addonModel = $this->factory->getModel('addon');

        if (!empty($addonFilter)) {
            //check to see if the addon is enabled; if not redirect back to addons with a message to enable
            $addonEntity = $addonModel->getEntity($addonFilter);
            if ($addonEntity != null && !$addonEntity->isEnabled()) {
                $viewParameters = array(
                    'page' => $this->factory->getSession()->get('mautic.addon.page')
                );

                return $this->postActionRedirect(array(
                    'returnUrl'       => $this->generateUrl('mautic_addon_index', $viewParameters),
                    'viewParameters'  => $viewParameters,
                    'contentTemplate' => 'MauticAddonBundle:Addon:index',
                    'passthroughVars' => array(
                        'activeLink'    => '#mautic_addon_index',
                        'mauticContent' => 'addon'
                    ),
                    'flashes'         => array(
                        array(
                            'type'    => 'error',
                            'msg'     => 'mautic.integration.error.addonnotenabled',
                            'msgVars' => array('%name%' => $addonEntity->getName())
                        )
                    )
                ));
            }
        }

        /** @var \Mautic\AddonBundle\Helper\IntegrationHelper $integrationHelper */
        $integrationHelper  = $this->factory->getHelper('integration');
        $integrationObjects = $integrationHelper->getIntegrationObjects(null, null, true);
        $integrations       = array();

        foreach ($integrationObjects as $name => $object) {
            $settings            = $object->getIntegrationSettings();
            $integrations[$name] = array(
                'name'    => $object->getName(),
                'display' => $object->getDisplayName(),
                'icon'    => $integrationHelper->getIconPath($object),
                'enabled' => $settings->isPublished(),
                'addon'   => $settings->getAddon()->getId()
            );
        }

        //sort by name
        ksort($integrations);

        $tmpl = $this->request->isXmlHttpRequest() ? $this->request->get('tmpl', 'index') : 'index';

        //get a list of addons for filter
        $addons = $addonModel->getEnabledList();

        return $this->delegateView(array(
            'viewParameters'  => array(
                'items'       => $integrations,
                'tmpl'        => $tmpl,
                'addonFilter' => ($addonFilter) ? array('id' => $addonEntity->getId(), 'name' => $addonEntity->getName()) : false,
                'addons'      => $addons
            ),
            'contentTemplate' => 'MauticAddonBundle:Integration:grid.html.php',
            'passthroughVars' => array(
                'activeLink'    => '#mautic_addon_integration_index',
                'mauticContent' => 'integration',
                'route'         => $this->generateUrl('mautic_addon_integration_index'),
            )
        ));
    }

    /**
     * @param string $name
     *
     * @return JsonResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function editAction ($name)
    {
        if (!$this->factory->getSecurity()->isGranted('addon:addons:manage')) {
            return $this->accessDenied();
        }

        $authorize = $this->request->request->get('integration_details[in_auth]', false, true);

        /** @var \Mautic\AddonBundle\Helper\IntegrationHelper $integrationHelper */
        $integrationHelper  = $this->factory->getHelper('integration');
        $integrationObject  = $integrationHelper->getIntegrationObject($name);

        // Verify that the requested integration exists
        if (empty($integrationObject)) {
            throw $this->createNotFoundException($this->get('translator')->trans('mautic.core.url.error.404'));
        }

        $leadFields = $this->factory->getModel('addon')->getLeadFields();

        /** @var \Mautic\AddonBundle\Integration\AbstractIntegration $integrationObject */
        $entity = $integrationObject->getIntegrationSettings();

        $form = $this->createForm('integration_details', $entity, array(
            'integration'        => $entity->getName(),
            'lead_fields'        => $leadFields,
            'integration_object' => $integrationObject,
            'action'             => $this->generateUrl('mautic_addon_integration_edit', array('name' => $name))
        ));

        if ($this->request->getMethod() == 'POST') {
            $valid = false;
            if (!$cancelled = $this->isFormCancelled($form)) {
                $currentKeys            = $integrationObject->getDecryptedApiKeys($entity);
                $currentFeatureSettings = $entity->getFeatureSettings();

                if ($valid = $this->isFormValid($form)) {
                    $em          = $this->factory->getEntityManager();
                    $integration = $entity->getName();

                    //merge keys
                    $keys = $form['apiKeys']->getData();

                    //restore original keys then merge the new ones to keep the form from wiping out empty secrets
                    $mergedKeys = $integrationObject->mergeApiKeys($keys, $currentKeys, true);
                    $integrationObject->encryptAndSetApiKeys($mergedKeys, $entity);

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
                        /** @var \Mautic\AddonBundle\Integration\AbstractIntegration $integrationObject */
                        $oauthUrl = $integrationObject->getAuthLoginUrl();

                        return new JsonResponse(array(
                            'integration'         => $integration,
                            'authUrl'             => $oauthUrl,
                            'authorize'           => 1,
                            'popupBlockerMessage' => $this->factory->getTranslator()->trans('mautic.integration.oauth.popupblocked')
                        ));
                    }
                }
            }

            if (($cancelled || $valid) && !$authorize) {
                // Close the modal and return back to the list view
                return new JsonResponse(array(
                    'closeModal'    => 1,
                    'enabled'       => $entity->getIsPublished(),
                    'name'          => $integrationObject->getName(),
                    'mauticContent' => 'integration',
                ));
            }
        }

        $template    = $integrationObject->getFormTemplate();
        $objectTheme = $integrationObject->getFormTheme();
        $default     = 'MauticAddonBundle:FormTheme\Integration';
        $themes      = array($default);
        if (is_array($objectTheme)) {
            $themes = array_merge($themes, $objectTheme);
        } else if ($objectTheme !== $default) {
            $themes[] = $objectTheme;
        }

        return $this->delegateView(array(
            'viewParameters'  => array(
                'form'        => $this->setFormTheme($form, $template, $themes),
                'integration' => $integrationObject
            ),
            'contentTemplate' => $template,
            'passthroughVars' => array(
                'activeLink'    => '#mautic_addon_integration_index',
                'mauticContent' => 'integration',
                'route'         => false
            )
        ));
    }
}
