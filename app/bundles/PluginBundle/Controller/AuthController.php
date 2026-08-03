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

final class AuthController extends FormController
{
    public function __construct(
        protected \Symfony\Component\Form\FormFactoryInterface $formFactory,
        protected \Mautic\FormBundle\Helper\FormFieldHelper $fieldHelper,
        \Doctrine\Persistence\ManagerRegistry $managerRegistry,
        \Mautic\CoreBundle\Factory\ModelFactory $modelFactory,
        \Mautic\CoreBundle\Helper\UserHelper $userHelper,
        \Mautic\CoreBundle\Helper\CoreParametersHelper $coreParametersHelper,
        \Symfony\Component\EventDispatcher\EventDispatcherInterface $dispatcher,
        \Mautic\CoreBundle\Translation\Translator $translator,
        \Mautic\CoreBundle\Service\FlashBag $flashBag,
        \Symfony\Component\HttpFoundation\RequestStack $requestStack,
        \Mautic\CoreBundle\Security\Permissions\CorePermissions $security,
        private readonly IntegrationHelper $integrationHelper,
    ) {
        parent::__construct($formFactory, $fieldHelper, $managerRegistry, $modelFactory, $userHelper, $coreParametersHelper, $dispatcher, $translator, $flashBag, $requestStack, $security);
    }

    /**
     * @param string $integration
     *
     * @return JsonResponse
     */
    public function authCallbackAction(Request $request, $integration): JsonResponse|RedirectResponse
    {
        $isAjax  = $request->isXmlHttpRequest();
        $session = $request->getSession();

        $integrationObject = $this->integrationHelper->getIntegrationObject($integration);

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

    public function authUserAction($integration): RedirectResponse
    {
        $integrationObject = $this->integrationHelper->getIntegrationObject($integration);

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
