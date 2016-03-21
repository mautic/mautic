<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PluginBundle\Controller;

use Mautic\CoreBundle\Controller\FormController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;

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
    public function authCallbackAction ($integration)
    {
        $isAjax = $this->request->isXmlHttpRequest();

        /** @var \Mautic\PluginBundle\Helper\IntegrationHelper $integrationHelper */
        $integrationHelper  = $this->factory->getHelper('integration');
        $integrationObjects = $integrationHelper->getIntegrationObjects(null, null, true);

        // We receive a lowercase name, so we need to convert the $integrationObjects array keys to lowercase
        $objects = array();

        foreach ($integrationObjects as $key => $value) {
            $objects[strtolower($key)] = $value;
        }

        $session = $this->factory->getSession();

        //check to see if the service exists
        if (!array_key_exists(strtolower($integration), $objects)) {
            $session->set('mautic.integration.postauth.message', array('mautic.integration.notfound', array('%name%' => $integration), 'error'));
            if ($isAjax) {
                return new JsonResponse(array('url' => $this->generateUrl('mautic_integration_auth_postauth')));
            } else {
                return new RedirectResponse($this->generateUrl('mautic_integration_auth_postauth'));
            }
        }

        $state      = $session->get($integration . '_csrf_token', false);
        $givenState = ($isAjax) ? $this->request->request->get('state') : $this->request->get('state');
        if ($state && $state !== $givenState) {
            $session->remove($integration . '_csrf_token');
            $session->set('mautic.integration.postauth.message', array('mautic.integration.auth.invalid.state', array(), 'error'));
            if ($isAjax) {
                return new JsonResponse(array('url' => $this->generateUrl('mautic_integration_auth_postauth')));
            } else {
                return new RedirectResponse($this->generateUrl('mautic_integration_auth_postauth'));
            }
        }

        $error = $integrationObjects[$integration]->authCallback();

        //check for error
        if ($error) {
            $type    = 'error';
            $message = 'mautic.integration.error.oauthfail';
            $params  = array('%error%' => $error);
        } else {
            $type    = 'notice';
            $message = 'mautic.integration.notice.oauthsuccess';
            $params  = array();
        }

        $session->set('mautic.integration.postauth.message', array($message, $params, $type));

        return new RedirectResponse($this->generateUrl('mautic_integration_auth_postauth'));
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function authStatusAction ()
    {
        $session     = $this->factory->getSession();
        $postMessage = $session->get('mautic.integration.postauth.message');
        $message     = $type = '';
        $alert       = 'success';
        if (!empty($postMessage)) {
            $message = $this->factory->getTranslator()->trans($postMessage[0], $postMessage[1], 'flashes');
            $session->remove('mautic.integration.postauth.message');
            $type = $postMessage[2];
            if ($type == 'error') {
                $alert = 'danger';
            }
        }

        return $this->render('MauticPluginBundle:Auth:postauth.html.php', array('message' => $message, 'alert' => $alert));
    }
}
