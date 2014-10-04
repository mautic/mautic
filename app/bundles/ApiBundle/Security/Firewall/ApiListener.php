<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ApiBundle\Security\Firewall;

use Mautic\CoreBundle\Factory\MauticFactory;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\Security\Http\Firewall\ListenerInterface;
use Symfony\Component\Security\Core\SecurityContextInterface;
use Symfony\Component\Security\Core\Authentication\AuthenticationManagerInterface;

class ApiListener implements ListenerInterface
{
    protected $securityContext;
    protected $authenticationManager;
    protected $factory;

    public function __construct (SecurityContextInterface $securityContext, AuthenticationManagerInterface $authenticationManager, MauticFactory $factory)
    {
        $this->securityContext       = $securityContext;
        $this->authenticationManager = $authenticationManager;
        $this->factory               = $factory;
        $this->supportedModes        = array('oauth1', 'oauth2');
    }

    public function handle (GetResponseEvent $event)
    {
        $apiEnabled = $this->factory->getParameter('api_enabled');
        $apiMode    = $this->factory->getParameter('api_mode');
        $request    = $event->getRequest();
        $params     = $request->attributes->get('oauth_request_parameters');
        $accessKey  = $request->get('access_token', false);
        $queryString = $request->server->get('QUERY_STRING');
        $translator = $this->factory->getTranslator();

        if (!empty($params['oauth_consumer_key'])) {
            $attemptedMode = 'oauth1';
        } elseif (!empty($accessKey) || strpos($queryString, 'access_token') !== false) {
            $attemptedMode = 'oauth2';
        } else {
            $attemptedMode = 'none';
        }

        if (empty($apiEnabled)) {
            $msg = $translator->trans('mautic.api.auth.error.apidisabled');
        } elseif (!in_array($apiMode, $this->supportedModes)) {
            $msg = $translator->trans('mautic.api.auth.error.apinotsupported', array('%mode%' => $apiMode));
        } elseif ($attemptedMode == 'none') {
            $msg = $translator->trans('mautic.api.auth.error.accessdenied');
        } elseif ($attemptedMode !== $apiMode) {
            $msg = $translator->trans('mautic.api.auth.error.notenabled', array('%mode%' => ucfirst($attemptedMode)));
        } else {
            //all checked out so continue to other the other auth providers
            return;
        }

        $response = new Response();
        $response->setContent($msg);
        $response->setStatusCode(Response::HTTP_FORBIDDEN);
        $event->setResponse($response);
    }
}