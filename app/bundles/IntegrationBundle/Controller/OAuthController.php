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
 * Class OAuthController
 */
class OAuthController extends FormController
{
    /**
     * @param string $connector
     *
     * @return JsonResponse
     */
    public function oAuth2CallbackAction($connector)
    {
        $isAjax = $this->request->isXmlHttpRequest();

        /** @var \Mautic\IntegrationBundle\Helper\ConnectorIntegrationHelper $connectorHelper */
        $connectorHelper  = $this->factory->getHelper('connector');
        $connectorObjects = $connectorHelper->getConnectorObjects(null, null, true);

        // We receive a lowercase name, so we need to convert the $connectorObjects array keys to lowercase
        $objects = array();

        foreach ($connectorObjects as $key => $value) {
            $objects[strtolower($key)] = $value;
        }

        //check to see if the service exists
        if (!array_key_exists(strtolower($connector), $objects)) {
            $this->request->getSession()->getFlashBag()->add('error', $connector . ' not found!');
            if ($isAjax) {
                return new JsonResponse(array('url' => $this->generateUrl('mautic_integration_oauth_postauth')));
            } else {
                return $this->render('MauticIntegrationBundle:Auth:postauth.html.php');
            }
        }

        $session    = $this->factory->getSession();
        $state      = $session->get($connector . '_csrf_token', false);
        $givenState = ($isAjax) ? $this->request->request->get('state') : $this->request->get('state');
        if ($state && $state !== $givenState) {
            $this->request->getSession()->getFlashBag()->add('error', 'Invalid CSRF token!');
            if ($isAjax) {
                return new JsonResponse(array('url' => $this->generateUrl('mautic_integration_oauth_postauth')));
            } else {
                return $this->render('MauticIntegrationBundle:Auth:postauth.html.php');
            }
        }

        if (!$isAjax) {
            //redirected from SM site with code so obtain access_token via ajax

            return $this->render('MauticIntegrationBundle:Auth:auth.html.php', array(
                'connector'     => $connector,
                'csrfToken'   => $state,
                'code'        => $this->request->get('code'),
                'callbackUrl' => $this->generateUrl('mautic_integration_oauth_callback', array('connector' => $connector), true)
            ));
        }

        //access token obtained so now get it and save it
        $session->remove($connector . '_csrf_token');

        //make the callback to the service to get the access code
        $clientId     = $this->request->request->get('clientId');
        $clientSecret = $this->request->request->get('clientSecret');

        list($entity, $error) = $connectorObjects[$connector]->oAuthCallback($clientId, $clientSecret);

        //check for error
        if ($error) {
            $type = 'error';
            $message = 'mautic.connector.error.oauthfail';
            $params = array('%error%' => $error);
        } else {
            $type = 'notice';
            $message = 'mautic.connector.notice.oauthsuccess';
            $params = array();
        }

        $this->request->getSession()->getFlashBag()->add(
            $type,
            $this->get('translator')->trans($message, $params, 'flashes')
        );

        return new JsonResponse(array('url' => $this->generateUrl('mautic_integration_oauth_postauth')));
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function oAuthStatusAction()
    {
        return $this->render('MauticIntegrationBundle:Auth:postauth.html.php');
    }
}
