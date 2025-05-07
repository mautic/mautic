<?php

declare(strict_types=1);

namespace Mautic\UserBundle\Security\Authenticator;

use OAuth2\OAuth2ServerException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

class Oauth2Authenticator extends \FOS\OAuthServerBundle\Security\Authenticator\Oauth2Authenticator
{
    public function supports(Request $request): ?bool
    {
        // needed until the oAuth2 library will not be updated to 4.0.5
        return null !== $this->serverService->getBearerToken($request);
    }

    /**
     * A BC compatible response.
     */
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        $previous = $exception->getPrevious();
        if ($previous instanceof OAuth2ServerException) {
            return $previous->getHttpResponse();
        }

        return parent::onAuthenticationFailure($request, $exception);
    }
}
