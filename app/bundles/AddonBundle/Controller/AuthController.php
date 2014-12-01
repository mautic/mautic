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
 * Class AuthController
 */
class AuthController extends FormController
{
    /**
     * @param string $integration
     *
     * @return JsonResponse
     */
    public function oAuth2CallbackAction($integration)
    {
        $isAjax = $this->request->isXmlHttpRequest();

        /** @var \Mautic\AddonBundle\Helper\IntegrationHelper $integrationHelper */
        $integrationHelper  = $this->factory->getHelper('integration');
        $integrationObjects = $integrationHelper->getIntegrationObjects(null, null, true);

        // We receive a lowercase name, so we need to convert the $integrationObjects array keys to lowercase
        $objects = array();

        foreach ($integrationObjects as $key => $value) {
            $objects[strtolower($key)] = $value;
        }

        //check to see if the service exists
        if (!array_key_exists(strtolower($integration), $objects)) {
            $this->request->getSession()->getFlashBag()->add('error', $integration . ' not found!');
            if ($isAjax) {
                return new JsonResponse(array('url' => $this->generateUrl('mautic_integration_oauth_postauth')));
            } else {
                return $this->render('MauticAddonBundle:Auth:postauth.html.php');
            }
        }

        $session    = $this->factory->getSession();
        $state      = $session->get($integration . '_csrf_token', false);
        $givenState = ($isAjax) ? $this->request->request->get('state') : $this->request->get('state');
        if ($state && $state !== $givenState) {
            $this->request->getSession()->getFlashBag()->add('error', 'Invalid CSRF token!');
            if ($isAjax) {
                return new JsonResponse(array('url' => $this->generateUrl('mautic_integration_oauth_postauth')));
            } else {
                return $this->render('MauticAddonBundle:Auth:postauth.html.php');
            }
        }

        if (!$isAjax) {
            //redirected from SM site with code so obtain access_token via ajax

            return $this->render('MauticAddonBundle:Auth:auth.html.php', array(
                'integration' => $integration,
                'csrfToken'   => $state,
                'code'        => $this->request->get('code'),
                'callbackUrl' => $this->generateUrl('mautic_integration_oauth_callback', array('integration' => $integration), true),
                'cookiesSet'  => $this->request->get('cookiesSet', 0)
            ));
        }

        $clientId     = $this->request->cookies->get('mautic_integration_clientid');
        $clientSecret = $this->request->cookies->get('mautic_integration_clientsecret');

        //access token obtained so now get it and save it
        $session->remove($integration . '_csrf_token');
        $session->remove('mautic_integration_clientid');
        $session->remove('mautic_integration_clientsecret');

        list($entity, $error) = $integrationObjects[$integration]->oAuthCallback($clientId, $clientSecret);

        //check for error
        if ($error) {
            $type = 'error';
            $message = 'mautic.integration.error.oauthfail';
            $params = array('%error%' => $error);
        } else {
            $type = 'notice';
            $message = 'mautic.integration.notice.oauthsuccess';
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
        return $this->render('MauticAddonBundle:Auth:postauth.html.php');
    }
}
