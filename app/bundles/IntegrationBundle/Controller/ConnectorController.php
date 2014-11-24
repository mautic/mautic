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

/**
 * Class ConnectorController
 */
class ConnectorController extends FormController
{
    /**
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\Response
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
            'contentTemplate' => 'MauticIntegrationBundle:Connector:list.html.php',
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
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\Response
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

        // TODO - Coming soon, we'll actually save your data ;-)

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
