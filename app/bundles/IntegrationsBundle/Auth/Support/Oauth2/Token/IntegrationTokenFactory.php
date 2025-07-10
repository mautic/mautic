<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Auth\Support\Oauth2\Token;

use kamermans\OAuth2\Token\TokenInterface;

class IntegrationTokenFactory implements TokenFactoryInterface
{
    /**
     * @param mixed[]  $extraKeysToStore Extra keys returned by the service during the token process that needs to be captured
     * @param int|null $defaultExpiresIn Default time in seconds that tokens are good for if not given in the response
     */
    public function __construct(
        private array $extraKeysToStore = [],
        private ?int $defaultExpiresIn = null,
    ) {
    }

    public function __invoke(array $data, ?TokenInterface $previousToken = null): IntegrationToken
    {
        $accessToken  = null;
        $refreshToken = null;

        // Read "access_token" attribute
        if (isset($data['access_token'])) {
            $accessToken = $data['access_token'];
        }

        // Read "refresh_token" attribute
        if (isset($data['refresh_token'])) {
            $refreshToken = $data['refresh_token'];
        } elseif (null !== $previousToken) {
            // When requesting a new access token with a refresh token, the
            // server may not resend a new refresh token. In that case we
            // should keep the previous refresh token as valid.
            //
            // See http://tools.ietf.org/html/rfc6749#section-6
            $refreshToken = $previousToken->getRefreshToken();
        }

        $expiresAt = $this->getExpiration($data);

        return new IntegrationToken($accessToken, $refreshToken, $expiresAt, $this->getExtraData($data));
    }

    private function getExtraData(array $data): array
    {
        $extraData = [];
        foreach ($this->extraKeysToStore as $key) {
            $extraData[$key] = $data[$key] ?? null;
        }

        return $extraData;
    }

    /**
     * @param mixed[] $data
     */
    private function getExpiration(array $data): ?int
    {
        // Read the "expires_at" attribute
        if (isset($data['expires_at'])) {
            return (int) $data['expires_at'];
        }

        // Read the "expires_in" attribute
        if (isset($data['expires_in'])) {
            return time() + (int) $data['expires_in'];
        }

        // Facebook unfortunately breaks the spec by using 'expires' instead of 'expires_in'
        if (isset($data['expires'])) {
            return time() + (int) $data['expires'];
        }

        // Fallback to the default if set
        if ($this->defaultExpiresIn) {
            return time() + $this->defaultExpiresIn;
        }

        return null;
    }
}
