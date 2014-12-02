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

        /** @var \Mautic\AddonBundle\Helper\IntegrationHelper $integrationHelper */
        $integrationHelper  = $this->factory->getHelper('integration');
        $integrationObjects = $integrationHelper->getIntegrationObjects(null, null, true);
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
            $leadFields['mautic.lead.field.group.' . $f->getGroup()][$f->getId()] = $f->getLabel();
        }

        // Sort the groups
        uksort($leadFields, 'strnatcmp');

        // Sort each group by translation
        foreach ($leadFields as $group => &$fieldGroup) {
            uasort($fieldGroup, 'strnatcmp');
        }

        $form = $this->createForm('integration_details', $integrationObject->getIntegrationSettings(), array(
            'integration'        => $integrationObject->getIntegrationSettings()->getName(),
            'lead_fields'        => $leadFields,
            'integration_object' => $integrationObject,
            'action'             => $this->generateUrl('mautic_addon_integration_edit', array('name' => $name))
        ));

        if ($this->request->getMethod() == 'POST') {
            if (!$cancelled = $this->isFormCancelled($form)) {
                if ($this->isFormValid($form)) {
                    $em          = $this->factory->getEntityManager();
                    $entity      = $integrationObject->getIntegrationSettings();
                    $integration = $entity->getName();
                    $currentKeys = $entity->getApiKeys();

                    // Check to make sure secret keys were not wiped out
                    if (!empty($currentKeys['clientId'])) {
                        $newKeys = $entity->getApiKeys();
                        if (!empty($currentKeys[$integration]['clientSecret']) && empty($newKeys['clientSecret'])) {
                            $newKeys['clientSecret'] = $currentKeys['clientSecret'];
                            $entity->setApiKeys($newKeys);
                        }
                    }

                    if (!$authorize) {
                        $features = $entity->getSupportedFeatures();
                        if (in_array('public_profile', $features) || in_array('lead_push', $features)) {
                            //make sure now non-existent aren't saved
                            $featureSettings = $entity->getFeatureSettings();
                            if (isset($featureSettings['leadFields'])) {
                                $fields                        = $integrationHelper->getAvailableFields($integration);
                                $featureSettings['leadFields'] = array_intersect_key($featureSettings['leadFields'], $fields);
                                $entity->setFeatureSettings($featureSettings);
                            }
                        }
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

                    $this->request->getSession()->getFlashBag()->add(
                        'notice',
                        $this->get('translator')->trans('mautic.addon.notice.saved', array(
                            '%name%' => $integration
                        ), 'flashes')
                    );
                }
            }

            // Close the modal and return back to the list view
            $passthroughVars['closeModal'] = 1;
            $response                      = new JsonResponse($passthroughVars);
            $response->headers->set('Content-Length', strlen($response->getContent()));

            return $response;
        }

        return $this->delegateView(array(
            'viewParameters'  => array(
                'form' => $form->createView()
            ),
            'contentTemplate' => $integrationObject->getFormTemplate(),
            'passthroughVars' => array(
                'activeLink'    => '#mautic_addon_integration_index',
                'mauticContent' => 'integration',
                'route'         => $this->generateUrl('mautic_addon_integration_edit', array('name' => $name))
            )
        ));
    }
}
