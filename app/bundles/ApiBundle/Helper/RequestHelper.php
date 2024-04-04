<?php

namespace Mautic\ApiBundle\Helper;

use Symfony\Component\HttpFoundation\Request;

class RequestHelper
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

        defined('MAUTIC_API_REQUEST') or define('MAUTIC_API_REQUEST', $isApiRequest);

        return $isApiRequest;
    }
}
