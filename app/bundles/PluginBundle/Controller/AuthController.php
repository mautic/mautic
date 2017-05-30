<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PluginBundle\Controller;

use Mautic\CoreBundle\Controller\FormController;
use Mautic\PluginBundle\Event\PluginIntegrationAuthRedirectEvent;
use Mautic\PluginBundle\PluginEvents;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Class AuthController.
 */
class AuthController extends FormController
{
    /**
     * @param string $integration
     *
     * @return JsonResponse
     */
    public function authCallbackAction($integration)
    {
        $isAjax  = $this->request->isXmlHttpRequest();
        $session = $this->get('session');

        /** @var \Mautic\PluginBundle\Helper\IntegrationHelper $integrationHelper */
        $integrationHelper = $this->factory->getHelper('integration');
        $integrationObject = $integrationHelper->getIntegrationObject($integration);

        //check to see if the service exists
        if (!$integrationObject) {
            $session->set('mautic.integration.postauth.message', ['mautic.integration.notfound', ['%name%' => $integration], 'error']);
            if ($isAjax) {
                return new JsonResponse(['url' => $this->generateUrl('mautic_integration_auth_postauth', ['integration' => $integration])]);
            } else {
                return new RedirectResponse($this->generateUrl('mautic_integration_auth_postauth', ['integration' => $integration]));
            }
        }

        try {
            $error = $integrationObject->authCallback();
        } catch (\InvalidArgumentException $e) {
            $session->set('mautic.integration.postauth.message', [$e->getMessage(), [], 'error']);
            $redirectUrl = $this->generateUrl('mautic_integration_auth_postauth', ['integration' => $integration]);
            if ($isAjax) {
                return new JsonResponse(['url' => $redirectUrl]);
            } else {
                return new RedirectResponse($redirectUrl);
            }
        }

        //check for error
        if ($error) {
            $type    = 'error';
            $message = 'mautic.integration.error.oauthfail';
            $params  = ['%error%' => $error];
        } else {
            $type    = 'notice';
            $message = 'mautic.integration.notice.oauthsuccess';
            $params  = [];
        }

        $session->set('mautic.integration.postauth.message', [$message, $params, $type]);

        $identifier[$integration] = null;
        $socialCache              = [];
        $userData                 = $integrationObject->getUserData($identifier, $socialCache);

        $session->set('mautic.integration.'.$integration.'.userdata', $userData);

        return new RedirectResponse($this->generateUrl('mautic_integration_auth_postauth', ['integration' => $integration]));
    }

    /**
     * @param $integration
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function authStatusAction($integration)
    {
        $postAuthTemplate = 'MauticPluginBundle:Auth:postauth.html.php';

        $session     = $this->get('session');
        $postMessage = $session->get('mautic.integration.postauth.message');
        $userData    = [];

        if (isset($integration)) {
            $userData = $session->get('mautic.integration.'.$integration.'.userdata');
        }

        $message = $type = '';
        $alert   = 'success';
        if (!empty($postMessage)) {
            $message = $this->translator->trans($postMessage[0], $postMessage[1], 'flashes');
            $session->remove('mautic.integration.postauth.message');
            $type = $postMessage[2];
            if ($type == 'error') {
                $alert = 'danger';
            }
        }

        return $this->render($postAuthTemplate, ['message' => $message, 'alert' => $alert, 'data' => $userData]);
    }

    /**
     * @param $integration
     *
     * @return RedirectResponse
     */
    public function authUserAction($integration)
    {
        /** @var \Mautic\PluginBundle\Helper\IntegrationHelper $integrationHelper */
        $integrationHelper = $this->factory->getHelper('integration');
        $integrationObject = $integrationHelper->getIntegrationObject($integration);

        $settings['method']      = 'GET';
        $settings['integration'] = $integrationObject->getName();

        /** @var \Mautic\PluginBundle\Integration\AbstractIntegration $integrationObject */
        $event = $this->dispatcher->dispatch(
            PluginEvents::PLUGIN_ON_INTEGRATION_AUTH_REDIRECT,
            new PluginIntegrationAuthRedirectEvent(
                $integrationObject,
                $integrationObject->getAuthLoginUrl()
            )
        );
        $oauthUrl = $event->getAuthUrl();

        $response = new RedirectResponse($oauthUrl);

        return $response;
    }
}
