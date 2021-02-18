<?php

/*
 * @copyright   2015 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ApiBundle\EventListener\OAuth1;

use Symfony\Component\HttpFoundation\Request;

/**
 * Class OAuthRequestListener.
 */
class OAuthRequestListener extends \Bazinga\OAuthServerBundle\EventListener\OAuthRequestListener
{
    /**
     * {@inheritdoc}
     *
     * @param Request $request The request
     *
     * @return array
     */
    protected function parseAuthorizationHeader(Request $request)
    {
        $authorization = false;

        if (!$request->headers->has('authorization')) {
            // Check to see if the header was added to $_SERVER by .htaccess
            //     RewriteCond %{HTTP:Authorization} .+
            //     RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]
            if ($request->server->has('REDIRECT_HTTP_AUTHORIZATION')) {
                $authorization = $request->server->get('REDIRECT_HTTP_AUTHORIZATION');
            } elseif ($request->server->has('HTTP_AUTHORIZATION')) {
                $authorization = $request->server->get('HTTP_AUTHORIZATION');
            }

            if ($authorization) {
                $request->headers->set('authorization', $authorization);
            }
        }

        return parent::parseAuthorizationHeader($request);
    }
}
