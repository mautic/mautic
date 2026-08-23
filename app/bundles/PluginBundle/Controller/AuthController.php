<?php

namespace Mautic\PluginBundle\Controller;

use Mautic\CoreBundle\Controller\FormController;
use Mautic\PluginBundle\Event\PluginIntegrationAuthRedirectEvent;
use Mautic\PluginBundle\Helper\IntegrationHelper;
use Mautic\PluginBundle\PluginEvents;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class AuthController extends FormController
{
    /**
     * @param string $integration
     */
    #[Route('/s/plugins/integrations/authcallback/{integration}', name: 'mautic_integration_auth_callback_secure')]
    #[Route('/plugins/integrations/authcallback/{integration}', name: 'mautic_integration_auth_callback')]
    public function authCallbackAction(Request $request, IntegrationHelper $integrationHelper, $integration): JsonResponse|RedirectResponse
    {
        $isAjax  = $request->isXmlHttpRequest();
        $session = $request->getSession();

        $integrationObject = $integrationHelper->getIntegrationObject($integration);

        // check to see if the service exists
        if (!$integrationObject) {
            $session->set('mautic.integration.postauth.message', ['mautic.integration.notfound', ['%name%' => $integration], 'error']);
            if ($isAjax) {
                return new JsonResponse(['url' => $this->generateUrl('mautic_integration_auth_postauth', ['integration' => $integration])]);
            }

            return new RedirectResponse($this->generateUrl('mautic_integration_auth_postauth', ['integration' => $integration]));
        }

        try {
            $error = $integrationObject->authCallback();
        } catch (\InvalidArgumentException $e) {
            $session->set('mautic.integration.postauth.message', [$e->getMessage(), [], 'error']);
            $redirectUrl = $this->generateUrl('mautic_integration_auth_postauth', ['integration' => $integration]);
            if ($isAjax) {
                return new JsonResponse(['url' => $redirectUrl]);
            }

            return new RedirectResponse($redirectUrl);
        }

        // check for error
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

    #[Route('/s/plugins/integrations/authstatus/{integration}', name: 'mautic_integration_auth_postauth_secure')]
    #[Route('/plugins/integrations/authstatus/{integration}', name: 'mautic_integration_auth_postauth')]
    public function authStatusAction(Request $request, $integration): Response
    {
        $postAuthTemplate = '@MauticPlugin/Auth/postauth.html.twig';

        $session     = $request->getSession();
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
            if ('error' == $type) {
                $alert = 'danger';
            }
        }

        return $this->render($postAuthTemplate, ['message' => $message, 'alert' => $alert, 'data' => $userData]);
    }

    #[Route('/plugins/integrations/authuser/{integration}', name: 'mautic_integration_auth_user')]
    public function authUserAction(IntegrationHelper $integrationHelper, $integration): RedirectResponse
    {
        $integrationObject = $integrationHelper->getIntegrationObject($integration);

        $settings['method']      = 'GET';
        $settings['integration'] = $integrationObject->getName();

        /** @var \Mautic\PluginBundle\Integration\AbstractIntegration $integrationObject */
        $event = $this->dispatcher->dispatch(
            new PluginIntegrationAuthRedirectEvent(
                $integrationObject,
                $integrationObject->getAuthLoginUrl()
            ),
            PluginEvents::PLUGIN_ON_INTEGRATION_AUTH_REDIRECT
        );
        $oauthUrl = $event->getAuthUrl();

        return new RedirectResponse($oauthUrl);
    }
}
