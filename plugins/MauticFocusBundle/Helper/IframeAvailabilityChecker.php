<?php
/*
 * @copyright   2020 Mautic, Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticFocusBundle\Helper;

use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Check if URL can be displayed via IFRAME.
 */
class IframeAvailabilityChecker
{
    public function check(string $url, string $currentScheme): JsonResponse
    {
        $response        = new JsonResponse();
        $responseContent = [];

        if ($this->checkProtocolMismatch($url, $currentScheme)) {
            $responseContent['errorMessage'] = "Protocol mismatch. Please use '{$currentScheme}://'";
        } else {
            $client = HttpClient::create([
                'headers' => [
                    'User-Agent' => 'Mautic',
                ],
            ]);

            $httpResponse = $client->request(Request::METHOD_GET, $url);

            $responseContent['errorMessage'] = $this->checkHeaders($httpResponse->getHeaders(false));
        }

        if ('' === $responseContent['errorMessage'] && Response::HTTP_OK === $httpResponse->getStatusCode()) {
            $responseContent['status'] = 1;
        } else {
            $responseContent['status'] = 0;
        }

        $response->setData($responseContent);

        return $response;
    }

    /**
     * Iframe doesn't allow cross protocol requests.
     */
    private function checkProtocolMismatch(string $url, string $currentScheme): bool
    {
        // Mixed Content: The page at 'https://example.com' was loaded over HTTPS,
        // but requested an insecure frame 'http://target-example.com/'. This request has been blocked; the content
        // must be served over HTTPS.
        return 0 !== strpos($url, $currentScheme.':');
    }

    /**
     * @param array $headers Content of Symfony\Contracts\HttpClient\ResponseInterface::getHeaders()
     *
     * @return string Error message if problem found
     */
    private function checkHeaders(array $headers): string
    {
        $return = '';

        if ($this->headerContains($headers, 'x-frame-options')) {
            // @see https://stackoverflow.com/questions/31944552/iframe-refuses-to-display
            return 'x-frame-options: SAMEORIGIN';
        }

        if ($this->headerContains($headers, 'Content-Security-Policy', "frame-ancestors 'self'")) {
            // https://seznam.cz
            // Refused to display 'https://www.seznam.cz/' in a frame because an ancestor violates the following
            // Content Security Policy directive: "frame-ancestors 'self'".
            // @see https://stackoverflow.com/questions/31944552/iframe-refuses-to-display
            return 'Content-Security-Policy';
        }

        return $return;
    }

    private function headerContains(array $headers, string $name, string $content = null): bool
    {
        if (array_key_exists($name, $headers)) {
            if (null !== $content) {
                if ($headers['name'] === $content) {
                    return true;
                } else {
                    return false;
                }
            }

            return true;
        }

        return false;
    }
}
