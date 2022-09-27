<?php

namespace Mautic\UserBundle\Controller;

use Mautic\CoreBundle\Controller\CommonController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\Security\Core\Exception as Exception;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

/**
 * Class DefaultController.
 */
class SecurityController extends CommonController
{
    /**
     * {@inheritdoc}
     */
    public function initialize(FilterControllerEvent $event)
    {
        /** @var \Symfony\Component\Security\Core\Authorization\AuthorizationChecker $authChecker */
        $authChecker = $this->get('security.authorization_checker');

        //redirect user if they are already authenticated
        if ($authChecker->isGranted('IS_AUTHENTICATED_FULLY') ||
            $authChecker->isGranted('IS_AUTHENTICATED_REMEMBERED')
        ) {
            $redirectUrl = $this->generateUrl('mautic_dashboard_index');
            $event->setController(function () use ($redirectUrl) {
                return new RedirectResponse($redirectUrl);
            });
        }
    }

    /**
     * Generates login form and processes login.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function loginAction(AuthenticationUtils $authenticationUtils)
    {
        // A way to keep the upgrade from failing if the session is lost after
        // the cache is cleared by upgrade.php
        if ($this->request->cookies->has('mautic_update')) {
            $step = $this->request->cookies->get('mautic_update');
            if ('clearCache' == $step) {
                // Run migrations
                $this->request->query->set('finalize', 1);

                return $this->forward('Mautic\CoreBundle\Controller\AjaxController::updateDatabaseMigrationAction',
                    [
                        'request' => $this->request,
                    ]
                );
            } elseif ('schemaMigration' == $step) {
                // Done so finalize
                return $this->forward('Mautic\CoreBundle\Controller\AjaxController::updateFinalizationAction',
                    [
                        'request' => $this->request,
                    ]
                );
            }

            /** @var \Mautic\CoreBundle\Helper\CookieHelper $cookieHelper */
            $cookieHelper = $this->factory->getHelper('cookie');
            $cookieHelper->deleteCookie('mautic_update');
        }

        $error = $authenticationUtils->getLastAuthenticationError();

        if (null !== $error) {
            if (($error instanceof Exception\BadCredentialsException)) {
                $msg = 'mautic.user.auth.error.invalidlogin';
            } elseif ($error instanceof Exception\DisabledException) {
                $msg = 'mautic.user.auth.error.disabledaccount';
            } else {
                $msg = $error->getMessage();
            }

            $this->addFlash($msg, [], 'error', null, false);
        }
        $this->request->query->set('tmpl', 'login');

        // Get a list of SSO integrations
        $integrationHelper = $this->get('mautic.helper.integration');
        $integrations      = $integrationHelper->getIntegrationObjects(null, ['sso_service'], true, null, true);

        return $this->delegateView([
            'viewParameters' => [
                'last_username' => $authenticationUtils->getLastUsername(),
                'integrations'  => $integrations,
            ],
            'contentTemplate' => 'MauticUserBundle:Security:login.html.php',
            'passthroughVars' => [
                'route'          => $this->generateUrl('login'),
                'mauticContent'  => 'user',
                'sessionExpired' => true,
            ],
        ]);
    }

    /**
     * Do nothing.
     */
    public function loginCheckAction()
    {
    }

    /**
     * The plugin should be handling this in it's listener.
     *
     * @param $integration
     *
     * @return RedirectResponse
     */
    public function ssoLoginAction($integration)
    {
        return new RedirectResponse($this->generateUrl('login'));
    }

    /**
     * The plugin should be handling this in it's listener.
     *
     * @param $integration
     *
     * @return RedirectResponse
     */
    public function ssoLoginCheckAction($integration)
    {
        // The plugin should be handling this in it's listener

        return new RedirectResponse($this->generateUrl('login'));
    }
}
