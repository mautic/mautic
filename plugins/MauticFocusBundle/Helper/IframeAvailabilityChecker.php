<?php

namespace MauticPlugin\MauticFocusBundle\Helper;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Check if URL can be displayed via IFRAME.
 */
class IframeAvailabilityChecker
{
    public function __construct(
        private TranslatorInterface $translator,
        private HttpClientInterface $httpClient,
        private CoreParametersHelper $coreParametersHelper,
    ) {
    }

    public function check(string $url, string $currentScheme): JsonResponse
    {
        $response        = new JsonResponse();
        $responseContent = [
            'status'       => 0,
            'errorMessage' => '',
        ];

        if ($this->checkProtocolMismatch($url, $currentScheme)) {
            $responseContent['errorMessage'] = $this->translator->trans(
                'mautic.focus.protocol.mismatch',
                [
                    '%url%' => str_replace('http://', 'https://', $url),
                ]);
        } else {
            $mauticUrl = $this->coreParametersHelper->get('site_url');

            try {
                $httpResponse = $this->httpClient->request(Request::METHOD_GET, $url);

                $blockingHeader = $this->checkHeaders($httpResponse->getHeaders(false), $url, $mauticUrl);

                if ('' !== $blockingHeader) {
                    $responseContent['errorMessage'] = $this->translator->trans(
                        'mautic.focus.blocking.iframe.header',
                        [
                            '%url%'    => $url,
                            '%header%' => $blockingHeader,
                        ]
                    );
                }
            } catch (\Exception $e) {
                // Transport exception with SSL cert for example
                $responseContent['errorMessage'] = $e->getMessage();
            }
        }

        if ('' === $responseContent['errorMessage'] && Response::HTTP_OK === $httpResponse->getStatusCode()) {
            $responseContent['status'] = 1;
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
        return 'https' === $currentScheme && str_starts_with($url, 'http://');
    }

    /**
     * @param array $headers Content of Symfony\Contracts\HttpClient\ResponseInterface::getHeaders()
     *
     * @return string Blocking header if problem found
     */
    private function checkHeaders(array $headers, string $externalUrl, string $mauticUrl): string
    {
        $return  = '';
        $headers = array_change_key_case($headers, CASE_LOWER);

        if ($this->headerContains($headers, 'x-frame-options')) {
            // @see https://stackoverflow.com/questions/31944552/iframe-refuses-to-display
            $return = 'x-frame-options: SAMEORIGIN';
        }

        if ($this->headerContains($headers, 'content-security-policy', 'frame-ancestors')) {
            // Refused to display 'https://www.seznam.cz/' in a frame because an ancestor violates the following
            // Content Security Policy directive: "frame-ancestors 'self'".
            // @see https://stackoverflow.com/questions/31944552/iframe-refuses-to-display
            // But according to the
            // @see https://developer.mozilla.org/en-US/docs/Web/HTTP/Reference/Headers/Content-Security-Policy/frame-ancestors
            // 'self' can be anywhere, and additionally this should pass if 'self' matches current URL.
            $header = $headers['content-security-policy'][0];

            $headerParts    = explode(';', $header);
            $frameAncestors = array_find(
                $headerParts,
                static fn (string $part): bool => str_starts_with(trim($part), 'frame-ancestors'));

            if (!is_string($frameAncestors)) {
                return $return;
            }

            $frameAncestors = trim($frameAncestors);

            if ('frame-ancestors' === $frameAncestors) {
                return $return;
            }

            $headerCSPValue      = trim(str_replace('frame-ancestors', '', $frameAncestors));
            $frameAncestorValues = array_map(static fn (string $value): string => trim($value), explode(' ', $headerCSPValue));
            $externalDomain      = \parse_url($externalUrl, \PHP_URL_HOST);
            $externalProtocol    = \parse_url($externalUrl, \PHP_URL_SCHEME);

            $return = 'content-security-policy';

            // Paths, IP address and wildcards (of path, IP) are not implemented.
            foreach ($frameAncestorValues as $frameAncestorValue) {
                // If IFrame is forbidden. Return the header to produce an error.
                if ("'none'" === $frameAncestorValue) {
                    return 'content-security-policy';
                }

                // If IFrame is hosted on the same domain as Mautic.
                if ("'self'" === $frameAncestorValue && $mauticUrl === $externalUrl) {
                    $return = '';
                }

                // If <scheme-source> matches.
                if ($externalProtocol.':' === $frameAncestorValue) {
                    $return = '';
                }

                // The "http:" also permits loading using https protocol.
                if ('https' === $externalProtocol && 'http:' === $frameAncestorValue) {
                    $return = '';
                }

                // If IFrame is hosted on exact allowed URL.
                if ($frameAncestorValue === $externalUrl) {
                    $return = '';
                }

                if (str_contains($frameAncestorValue, '//*.')) {
                    $cspValue  = str_replace('//*.', '//', $frameAncestorValue);
                    $cspDomain = \parse_url($cspValue, \PHP_URL_HOST);

                    if (str_ends_with($cspDomain, $externalDomain)) {
                        $return = '';
                    }
                }
            }
        }

        return $return;
    }

    private function headerContains(array $headers, string $name, ?string $content = null): bool
    {
        if (array_key_exists($name, $headers)) {
            if (null !== $content && array_key_exists(0, $headers[$name])) {
                return str_contains($headers[$name][0], $content);
            }

            return true;
        }

        return false;
    }
}
