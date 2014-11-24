<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\IntegrationBundle\Controller;

use Mautic\CoreBundle\Controller\FormController;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Class ConnectorController
 */
class ConnectorController extends FormController
{
    /**
     * @return JsonResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function indexAction()
    {
        //set some permissions
        $permissions = $this->factory->getSecurity()->isGranted(array(
            'integration:integrations:view',
            'integration:integrations:create',
            'integration:integrations:edit',
            'integration:integrations:delete'
        ), 'RETURN_ARRAY');

        if (!$permissions['integration:integrations:view']) {
            return $this->accessDenied();
        }

        /** @var \Mautic\IntegrationBundle\Helper\NetworkIntegrationHelper $networkHelper */
        $networkHelper  = $this->container->get('mautic.network.integration');
        $networkObjects = $networkHelper->getNetworkObjects(null, null, true);
        $connectors     = array();

        foreach ($networkObjects as $name => $object) {
            $connectors[] = array('name' => $name, 'icon' => $networkHelper->getIconPath($object));
        }

        $tmpl = $this->request->isXmlHttpRequest() ? $this->request->get('tmpl', 'index') : 'index';

        return $this->delegateView(array(
            'viewParameters'  =>  array(
                'items'       => $connectors,
                'permissions' => $permissions,
                'tmpl'        => $tmpl
            ),
            'contentTemplate' => 'MauticIntegrationBundle:Connector:grid.html.php',
            'passthroughVars' => array(
                'activeLink'     => '#mautic_integration_connector_index',
                'mauticContent'  => 'connector',
                'route'          => $this->generateUrl('mautic_integration_connector_index')
            )
        ));
    }

    /**
     * @param string $name
     *
     * @return JsonResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function editAction($name)
    {
        //set some permissions
        $permissions = $this->factory->getSecurity()->isGranted(array(
            'integration:integrations:view',
            'integration:integrations:create',
            'integration:integrations:edit',
            'integration:integrations:delete'
        ), 'RETURN_ARRAY');

        if (!$permissions['integration:integrations:edit']) {
            return $this->accessDenied();
        }

        /** @var \Mautic\IntegrationBundle\Helper\NetworkIntegrationHelper $networkHelper */
        $networkHelper  = $this->container->get('mautic.network.integration');
        $networkObjects = $networkHelper->getNetworkObjects(null, null, true);

        // We receive a lowercase name, so we need to convert the $networkObjects array keys to lowercase
        $objects = array();

        foreach ($networkObjects as $key => $value) {
            $objects[strtolower($key)] = $value;
        }

        // Verify that the requested connector exists
        if (!array_key_exists($name, $objects)) {
            throw $this->createNotFoundException($this->get('translator')->trans('mautic.core.url.error.404'));
        }

        /** @var \Mautic\SocialBundle\Network\AbstractNetwork $networkObject */
        $networkObject = $objects[$name];

        // Get a list of custom form fields
        $fields = $this->factory->getModel('lead.field')->getEntities(array('filter' => array('isPublished' => true)));

        $leadFields = array();

        foreach ($fields as $f) {
            $leadFields['mautic.lead.field.group.'. $f->getGroup()][$f->getId()] = $f->getLabel();
        }

        // Sort the groups
        uksort($leadFields, 'strnatcmp');

        // Sort each group by translation
        foreach ($leadFields as $group => &$fieldGroup) {
            uasort($fieldGroup, 'strnatcmp');
        }

        $form = $this->createForm('socialmedia_details', $networkObject->getSettings(), array(
            'sm_network'  => $networkObject->getSettings()->getName(),
            'lead_fields' => $leadFields,
            'sm_object'   => $networkObject,
            'action'      => $this->generateUrl('mautic_integration_connector_edit', array('name' => $name))
        ));

        if ($this->request->getMethod() == 'POST') {
            if (!$cancelled = $this->isFormCancelled($form)) {
                if ($this->isFormValid($form)) {
                    $em          = $this->factory->getEntityManager();
                    $entity      = $networkObject->getSettings();
                    $network     = $entity->getName();
                    $currentKeys = $entity->getApiKeys();

                    // Check to make sure secret keys were not wiped out
                    if (!empty($currentKeys['clientId'])) {
                        $newKeys = $entity->getApiKeys();
                        if (!empty($currentKeys[$network]['clientSecret']) && empty($newKeys['clientSecret'])) {
                            $newKeys['clientSecret'] = $currentKeys['clientSecret'];
                            $entity->setApiKeys($newKeys);
                        }
                    }

                    $features = $entity->getSupportedFeatures();
                    if (in_array('public_profile', $features)) {
                        //make sure now non-existent aren't saved
                        $featureSettings               = $entity->getFeatureSettings();
                        if (isset($featureSettings['leadFields'])) {
                            $fields                        = $networkHelper->getAvailableFields($network);
                            $featureSettings['leadFields'] = array_intersect_key($featureSettings['leadFields'], $fields);
                            $entity->setFeatureSettings($featureSettings);
                        }
                    }

                    $em->persist($entity);
                    $em->flush();

                    $this->request->getSession()->getFlashBag()->add(
                        'notice',
                        $this->get('translator')->trans('mautic.integration.notice.saved', array(
                            '%name%' => $network
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
            'viewParameters'  =>  array(
                'form'        => $form->createView()
            ),
            'contentTemplate' => 'MauticIntegrationBundle:Connector:form.html.php',
            'passthroughVars' => array(
                'activeLink'    => '#mautic_integration_connector_index',
                'mauticContent' => 'connector',
                'route'         => $this->generateUrl('mautic_integration_connector_edit', array('name' => $name))
            )
        ));
    }
}
