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

        $addonFilter = $this->request->get('addon');

        /** @var \Mautic\AddonBundle\Helper\IntegrationHelper $integrationHelper */
        $integrationHelper  = $this->factory->getHelper('integration');
        $integrationObjects = $integrationHelper->getIntegrationObjects(null, null, true, $addonFilter);
        $integrations       = array();

        foreach ($integrationObjects as $name => $object) {
            $integrations[$name] = array('name' => $name, 'icon' => $integrationHelper->getIconPath($object));
        }

        //sort by name
        ksort($integrations);

        $tmpl = $this->request->isXmlHttpRequest() ? $this->request->get('tmpl', 'index') : 'index';

        return $this->delegateView(array(
            'viewParameters'  => array(
                'items' => $integrations,
                'tmpl'  => $tmpl
            ),
            'contentTemplate' => 'MauticAddonBundle:Integration:grid.html.php',
            'passthroughVars' => array(
                'activeLink'    => '#mautic_addon_integration_index',
                'mauticContent' => 'integration',
                'route'         => $this->generateUrl('mautic_addon_integration_index')
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
        $integrationObjects = $integrationHelper->getIntegrationObjects(null, null, true);

        // We receive a lowercase name, so we need to convert the $integrationObjects array keys to lowercase
        $objects = array();

        foreach ($integrationObjects as $key => $value) {
            $objects[strtolower($key)] = $value;
        }

        // Verify that the requested integration exists
        if (!array_key_exists($name, $objects)) {
            throw $this->createNotFoundException($this->get('translator')->trans('mautic.core.url.error.404'));
        }

        $integrationObject = $objects[$name];

        // Get a list of custom form fields
        $fields = $this->factory->getModel('lead.field')->getEntities(array('filter' => array('isPublished' => true)));

        $leadFields = array();

        foreach ($fields as $f) {
            $leadFields['mautic.lead.field.group.' . $f->getGroup()][$f->getAlias()] = $f->getLabel();
        }

        // Sort the groups
        uksort($leadFields, 'strnatcmp');

        // Sort each group by translation
        foreach ($leadFields as $group => &$fieldGroup) {
            uasort($fieldGroup, 'strnatcmp');
        }

        /** @var \Mautic\AddonBundle\Integration\AbstractIntegration $integrationObject */
        $entity = $integrationObject->getIntegrationSettings();

        $form = $this->createForm('integration_details', $entity, array(
            'integration'        => $entity->getName(),
            'lead_fields'        => $leadFields,
            'integration_object' => $integrationObject,
            'action'             => $this->generateUrl('mautic_addon_integration_edit', array('name' => $name))
        ));

        $currentKeys = $entity->getApiKeys();
        $currentFeatureSettings = $entity->getFeatureSettings();

        if ($this->request->getMethod() == 'POST') {
            if (!$cancelled = $this->isFormCancelled($form)) {
                if ($this->isFormValid($form)) {
                    $em          = $this->factory->getEntityManager();
                    $integration = $entity->getName();

                    //merge keys
                    $keys = $form['apiKeys']->getData();
                    //restore original keys then merge the new ones to keep the form from wiping out empty secrets
                    $mergedKeys = $integrationObject->mergeApiKeys($keys, $currentKeys, true);
                    $entity->setApiKeys($mergedKeys);
                    if (!$authorize) {
                        $features = $entity->getSupportedFeatures();
                        if (in_array('public_profile', $features) || in_array('push_lead', $features)) {
                            //make sure now non-existent aren't saved
                            $featureSettings = $entity->getFeatureSettings();
                            if (isset($featureSettings['leadFields'])) {
                                $fields                        = $integrationHelper->getAvailableFields($integration);
                                if (!empty($fields)) {
                                    $featureSettings['leadFields'] = array_intersect_key($featureSettings['leadFields'], $fields);
                                    foreach ($featureSettings['leadFields'] as $f => $v) {
                                        if (empty($v)) {
                                            unset($featureSettings['leadFields'][$f]);
                                        }
                                    }
                                    $entity->setFeatureSettings($featureSettings);
                                }
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
                        $oauthUrl = $integrationObject->getOAuthLoginUrl();
                        return new JsonResponse(array(
                            'integration' => $integration,
                            'authUrl' => $oauthUrl,
                            'authorize' => 1,
                            'popupBlockerMessage' => $this->factory->getTranslator('mautic.integration.oauth.popupblocked')
                        ));
                    }
                }
            }

            // Close the modal and return back to the list view
            $passthroughVars['closeModal'] = 1;
            $response                      = new JsonResponse($passthroughVars);
            $response->headers->set('Content-Length', strlen($response->getContent()));

            return $response;
        }

        $template   = $integrationObject->getFormTemplate();
        $objectTheme = $integrationObject->getFormTheme();
        $default = 'MauticAddonBundle:FormTheme\Integration';
        $themes   = array($default);
        if (is_array($objectTheme)) {
            $themes = array_merge($themes, $objectTheme);
        } else if ($objectTheme !== $default) {
            $themes[] = $objectTheme;
        }

        return $this->delegateView(array(
            'viewParameters'  => array(
                'form' => $this->setFormTheme($form, $template, $themes),
            ),
            'contentTemplate' => $template,
            'passthroughVars' => array(
                'activeLink'    => '#mautic_addon_integration_index',
                'mauticContent' => 'integration',
                'route'         => $this->generateUrl('mautic_addon_integration_edit', array('name' => $name))
            )
        ));
    }
}
