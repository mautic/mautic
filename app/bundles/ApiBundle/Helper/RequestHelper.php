<?php

namespace Mautic\ApiBundle\Helper;

use Symfony\Component\HttpFoundation\Request;

final class RequestHelper
{
    public static function hasBasicAuth(Request $request): bool
    {
        return str_starts_with(strtolower((string) $request->headers->get('Authorization')), 'basic');
    }

    public static function isApiRequest(Request $request): bool
    {
        $requestUrl = $request->getRequestUri();

        // Check if /oauth or /api
        $isApiRequest = (str_contains($requestUrl, '/oauth') || str_contains($requestUrl, '/api'));

        defined('MAUTIC_API_REQUEST') || define('MAUTIC_API_REQUEST', $isApiRequest);

        return $isApiRequest;
    }
}
