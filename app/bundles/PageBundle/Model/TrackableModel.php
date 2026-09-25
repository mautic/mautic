<?php

namespace Mautic\PageBundle\Model;

use GuzzleHttp\Psr7\Uri;
use Mautic\CoreBundle\Helper\UrlHelper;
use Mautic\CoreBundle\Model\AbstractCommonModel;
use Mautic\LeadBundle\Entity\LeadFieldRepository;
use Mautic\LeadBundle\Helper\TokenHelper;
use Mautic\PageBundle\Entity\Redirect;
use Mautic\PageBundle\Entity\Trackable;
use Mautic\PageBundle\Entity\TrackableRepository;
use Mautic\PageBundle\Event\UntrackableUrlsEvent;
use Symfony\Contracts\Service\Attribute\Required;

/**
 * @extends AbstractCommonModel<Trackable>
 */
final class TrackableModel extends AbstractCommonModel
{
    public static function getName(): string
    {
        return 'page.trackable';
    }

    /**
     * Array of URLs and/or tokens that should not be converted to trackables.
     *
     * @var array
     */
    private $doNotTrack = [];

    /**
     * Tokens with values that could be used as URLs.
     */
    private array $contentTokens = [];

    /**
     * Stores content that needs to be replaced when URLs are parsed out of content.
     */
    private array $contentReplacements = [];

    /**
     * Indicates whether first-pass replacements were collected while parsing content.
     */
    private bool $hasFirstPassReplacements = false;

    private ?array $contactFieldUrlTokens = null;

    private RedirectModel $redirectModel;

    private LeadFieldRepository $leadFieldRepository;

    private TrackableRepository $trackableRepository;

    #[Required]
    public function autowireTrackableModel(
        RedirectModel $redirectModel,
        LeadFieldRepository $leadFieldRepository,
        TrackableRepository $trackableRepository,
    ): void {
        $this->redirectModel        = $redirectModel;
        $this->leadFieldRepository  = $leadFieldRepository;
        $this->trackableRepository  = $trackableRepository;
    }

    public function getRepository(): TrackableRepository
    {
        return $this->trackableRepository;
    }

    /**
     * @param bool|false           $shortenUrl   If true, use the configured shortener service to shorten the URLs
     * @param array                $utmTags
     * @param array<string, mixed> $clickthrough
     *
     * @return string
     */
    public function generateTrackableUrl(
        Trackable $trackable,
        array $clickthrough = [],
        bool $shortenUrl = false,
        $utmTags = [],
    ) {
        $clickthrough['channel'] ??= [$trackable->getChannel() => $trackable->getChannelId()];

        $redirect = $trackable->getRedirect();

        $trackedUrl = $this->redirectModel->generateRedirectUrl($redirect, $clickthrough);

        if ([] !== $utmTags) {
            $trackedUrl = $this->redirectModel->applyUtmTags($trackedUrl, $utmTags);
        }

        if ($shortenUrl) {
            return $this->redirectModel->shortenUrl($trackedUrl);
        }

        return $trackedUrl;
    }

    /**
     * Return a channel Trackable entity by URL.
     */
    public function getTrackableByUrl($url, $channel, $channelId): ?\Mautic\PageBundle\Entity\Trackable
    {
        if (empty($url)) {
            return null;
        }

        // Ensure the URL saved to the database does not have encoded ampersands
        $url = UrlHelper::decodeAmpersands($url);

        $trackable = $this->trackableRepository->findByUrl($url, $channel, $channelId);
        if (null == $trackable) {
            $trackable = $this->createTrackableEntity($url, $channel, $channelId);
            $this->trackableRepository->saveEntity($trackable->getRedirect());
            $this->trackableRepository->saveEntity($trackable);
        }

        return $trackable;
    }

    /**
     * Get Trackable entities by an array of URLs.
     *
     * @return array<Trackable>
     */
    public function getTrackablesByUrls($urls, $channel, $channelId): array
    {
        $uniqueUrls = array_unique(
            array_values($urls)
        );

        $trackables = $this->trackableRepository->findByUrls(
            $uniqueUrls,
            $channel,
            $channelId
        );

        $newRedirects  = [];
        $newTrackables = [];

        /** @var array<Trackable> $return */
        $return = [];

        /** @var array<string, Trackable> $byUrl */
        $byUrl = [];

        /** @var Trackable $trackable */
        foreach ($trackables as $trackable) {
            $url         = $trackable->getRedirect()->getUrl();
            $byUrl[$url] = $trackable;
        }

        foreach ($urls as $key => $url) {
            if (empty($url)) {
                continue;
            }

            if (isset($byUrl[$url])) {
                $return[$key] = $byUrl[$url];
            } else {
                $trackable = $this->createTrackableEntity($url, $channel, $channelId);
                // Redirect has to be saved first to have ID available
                $newRedirects[]  = $trackable->getRedirect();
                $newTrackables[] = $trackable;
                $return[$key]    = $trackable;
                // Keep track so it can be re-used if applicable
                $byUrl[$url] = $trackable;
            }
        }

        // Save new entities
        if (count($newRedirects)) {
            $this->trackableRepository->saveEntities($newRedirects);
        }
        if (count($newTrackables)) {
            $this->trackableRepository->saveEntities($newTrackables);
        }

        unset($trackables, $newRedirects, $newTrackables, $byUrl);

        return $return;
    }

    /**
     * Get a list of URLs that are tracked by a specific channel.
     *
     * @return mixed[]
     */
    public function getTrackableList($channel, $channelId): array
    {
        return $this->trackableRepository->findByChannel($channel, $channelId);
    }

    /**
     * Returns a list of tokens and/or URLs that should not be converted to trackables.
     *
     * @param string|string[]|null $content
     */
    public function getDoNotTrackList(string|array|null $content): array
    {
        /** @var UntrackableUrlsEvent $event */
        $event = $this->dispatcher->dispatch(
            new UntrackableUrlsEvent($content)
        );

        return $event->getDoNotTrackList();
    }

    /**
     * Extract URLs from content and return as trackables.
     *
     * @param string|string[] $content
     * @param string[]        $contentTokens
     * @param bool            $usingClickthrough Set to false if not using a clickthrough parameter.
     *                                           This is to ensure that URLs are built correctly with ? or & for
     *                                           URLs tracked that include query parameters
     *
     * @return array{string|string[],Redirect[]|Trackable[]}
     */
    public function parseContentForTrackables($content, array $contentTokens = [], ?string $channel = null, int|string|null $channelId = null, bool $usingClickthrough = true): array
    {
        // Set do not track list for validateUrlIsTrackable()
        $this->doNotTrack = $this->getDoNotTrackList($content);

        // Set content tokens used by validateUrlIsTrackable()
        $this->contentTokens = $contentTokens;

        $contentWasString = false;
        if (!is_array($content)) {
            $contentWasString = true;
            $content          = [$content];
        }

        $trackableTokens = [];
        foreach ($content as $key => $text) {
            $content[$key] = $this->parseContent($text, $channel, $channelId, $trackableTokens);
        }

        return [
            $contentWasString ? $content[0] : $content,
            $trackableTokens,
        ];
    }

    /**
     * Converts array of Trackable or Redirect entities into {trackable} tokens.
     *
     * @param array<string, Trackable|Redirect> $entities
     *
     * @return array<string, Redirect|Trackable>
     */
    private function createTrackingTokens(array $entities): array
    {
        $tokens = [];
        foreach ($entities as $trackable) {
            $redirect       = ($trackable instanceof Trackable) ? $trackable->getRedirect() : $trackable;
            $token          = '{trackable='.$redirect->getRedirectId().'}';
            $tokens[$token] = $trackable;

            // Store the URL to be replaced by a token
            $this->contentReplacements['second_pass'][$redirect->getUrl()] = $token;
        }

        return $tokens;
    }

    /**
     * Prepares content for tokenized trackable URLs by replacing them with {trackable=ID} tokens.
     *
     * @param string $type    html|text
     */
    private function prepareContentWithTrackableTokens(string $content, string $type): string
    {
        if (empty($content)) {
            return '';
        }

        // Simple search and replace to remove attributes, schema for tokens, and updating URL parameter order
        $firstPassSearch  = array_keys($this->contentReplacements['first_pass']);
        $firstPassReplace = $this->contentReplacements['first_pass'];
        $content          = str_ireplace($firstPassSearch, $firstPassReplace, $content);

        // Sort longer to shorter strings to ensure that URLs that share the same base are appropriately replaced
        uksort($this->contentReplacements['second_pass'], fn ($a, $b): int => strlen($b) - strlen($a));

        if ('html' === $type) {
            // Hours spent trying to handle through \DomDocument: 9h. The issue is that tokens "{token}" is replaced
            // by the \DomDocument::save will encode those on all doc, but here we need to replace only `href`.
            foreach ($this->contentReplacements['second_pass'] as $search => $replace) {
                // Make the search regular expression match both "&" and "&amp;".
                $search  = preg_quote($search, '/');
                $search  = str_replace('&amp;', '&', $search);
                $search  = str_replace('&', '(?:&|&amp;)', $search);
                $content = preg_replace(
                    '/<(.*?) href=(["\'])(?:\R|)(?:\s*)'.$search.'(.*?)(?:\s*)(?:\R|)\\2(.*?)>/i',
                    '<$1 href=$2'.$replace.'$3$2$4>',
                    $content
                );
            }
        } else {
            // For text, just do a simple search/replace
            $secondPassSearch  = array_keys($this->contentReplacements['second_pass']);
            $secondPassReplace = $this->contentReplacements['second_pass'];
            $content           = str_ireplace($secondPassSearch, $secondPassReplace, $content);
        }

        return $content;
    }

    /**
     * @phpstan-impure
     */
    private function extractTrackablesFromContent(string $content): array
    {
        if (0 !== preg_match('/<[^<]+>/', $content)) {
            // Parse as HTML
            return $this->extractTrackablesFromHtml($content);
        }

        // Parse as plain text
        return $this->extractTrackablesFromText($content);
    }

    /**
     * Find URLs in HTML and parse into trackables.
     *
     * @param string $html HTML content
     */
    private function extractTrackablesFromHtml(string $html): array
    {
        // Find links using DOM to only find <a> tags
        $libxmlPreviousState = libxml_use_internal_errors(true);
        libxml_use_internal_errors(true);
        $dom = new \DOMDocument();
        $dom->loadHTML('<?xml encoding="UTF-8">'.$html);
        libxml_clear_errors();
        libxml_use_internal_errors($libxmlPreviousState);
        $links = $dom->getElementsByTagName('a');

        $xpath = new \DOMXPath($dom);
        $maps  = $xpath->query('//map/area');

        return array_merge($this->extractTrackables($links), $this->extractTrackables($maps));
    }

    /**
     * Find URLs in plain text and parse into trackables.
     *
     * @param string $text Plain text content
     */
    private function extractTrackablesFromText(string $text): array
    {
        // Remove any HTML tags (such as img) that could contain href or src attributes prior to parsing for links
        $text = strip_tags($text);

        // Get a list of URL type contact fields
        $allUrls       = UrlHelper::getUrlsFromPlaintext($text, $this->getContactFieldUrlTokens());
        $trackableUrls = [];

        foreach ($allUrls as $url) {
            if ($preparedUrl = $this->prepareUrlForTracking($url)) {
                [$urlKey, $urlValue]     = $preparedUrl;
                $trackableUrls[$urlKey]  = $urlValue;
            }
        }

        return $trackableUrls;
    }

    private function createTrackableEntity(string $url, $channel, $channelId): Trackable
    {
        $redirect = $this->redirectModel->createRedirectEntity($url);

        $trackable = new Trackable();
        $trackable->setChannel($channel)
            ->setChannelId($channelId)
            ->setRedirect($redirect);

        return $trackable;
    }

    /**
     * Validate and parse link for tracking.
     *
     * @return false|array{0: string, 1: string}
     */
    private function prepareUrlForTracking(string $url): false|array
    {
        // Ensure it's clean
        $url = trim($url);

        // Ensure ampersands are & for the sake of parsing
        $url = UrlHelper::decodeAmpersands($url);

        // If this is just a token, validate it's supported before going further
        if (preg_match('/^{.*?}$/i', $url) && !$this->validateTokenIsTrackable($url)) {
            return false;
        }

        // Default key and final URL to the given $url
        $trackableKey = $trackableUrl = $url;

        // Convert URL
        $urlParts = parse_url($url);

        // We need to ignore not parsable and invalid urls
        if (false === $urlParts || !$this->isValidUrl($urlParts, false)) {
            return false;
        }

        // Check if URL is trackable
        $tokenizedHost = (!isset($urlParts['host']) && isset($urlParts['path'])) ? $urlParts['path'] : $urlParts['host'];
        if (preg_match('/^(\{\S+?\})/', $tokenizedHost, $match)) {
            $token = $match[1];

            // Tokenized hosts that are standalone tokens shouldn't use a scheme since the token value should contain it
            if ($token === $tokenizedHost && $scheme = (!empty($urlParts['scheme'])) ? $urlParts['scheme'] : false) {
                // Token has a schema so let's get rid of it before replacing tokens
                $this->contentReplacements['first_pass'][$scheme.'://'.$tokenizedHost] = $tokenizedHost;
                $this->hasFirstPassReplacements                                        = true;
                unset($urlParts['scheme']);
            }

            // Validate that the token is something that can be trackable
            if (!$this->validateTokenIsTrackable($token, $tokenizedHost)) {
                return false;
            }

            // Do not convert contact tokens
            if (!$this->isContactFieldToken($token)) {
                $trackableUrl = (!empty($urlParts['query'])) ? $this->contentTokens[$token].'?'.$urlParts['query'] : $this->contentTokens[$token];
                $trackableKey = $trackableUrl;

                // Replace the URL token with the actual URL
                $this->contentReplacements['first_pass'][$url]  = $trackableUrl;
                $this->hasFirstPassReplacements                 = true;
            }
        } else {
            // Regular URL without a tokenized host
            try {
                $trackableUrl = $this->httpBuildUrl($urlParts);
            } catch (\InvalidArgumentException) {
                return false;
            }

            if ($this->isInDoNotTrack($trackableUrl)) {
                return false;
            }
        }

        if ($this->isInDoNotTrack($trackableUrl)) {
            return false;
        }

        return [$trackableKey, $trackableUrl];
    }

    /**
     * Determines if a URL/token is in the do not track list.
     */
    private function isInDoNotTrack($url): bool
    {
        // Ensure it's not in the do not track list
        foreach ($this->doNotTrack as $notTrackable) {
            if (preg_match('~'.$notTrackable.'~', $url)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Validates that a token is trackable as a URL.
     */
    private function validateTokenIsTrackable(string $token, $tokenizedHost = null): bool
    {
        // Validate if this token is listed as not to be tracked
        if ($this->isInDoNotTrack($token)) {
            return false;
        }

        if ($this->isContactFieldToken($token)) {
            // Assume it's true as the redirect methods should handle this dynamically
            return true;
        }

        $tokenValue = TokenHelper::getValueFromTokens($this->contentTokens, $token);

        // Validate that the token is available
        if (!$tokenValue) {
            return false;
        }

        if ($tokenizedHost) {
            $url = str_ireplace($token, $tokenValue, $tokenizedHost);

            return $this->isValidUrl($url, false);
        }

        return $this->isValidUrl($tokenValue);
    }

    private function isValidUrl($url, bool $forceScheme = true): bool
    {
        $urlParts = (!is_array($url)) ? parse_url($url) : $url;

        // Ensure a applicable URL (rule out URLs as just #)
        if (!isset($urlParts['host']) && !isset($urlParts['path'])) {
            return false;
        }

        // Ensure a valid scheme
        return (!$forceScheme || isset($urlParts['scheme'])) && (!isset($urlParts['scheme']) || in_array(
            $urlParts['scheme'],
            ['http', 'https', 'ftp', 'ftps', 'mailto']
        ));
    }

    /**
     * @return array<string, Trackable|Redirect>
     */
    private function getEntitiesFromUrls(array $trackableUrls, ?string $channel, ?int $channelId): array
    {
        if (!empty($channel) && !empty($channelId)) {
            // Track as channel aware
            return $this->getTrackablesByUrls($trackableUrls, $channel, $channelId);
        }

        // Simple redirects
        return $this->redirectModel->getRedirectsByUrls($trackableUrls);
    }

    /**
     * Build a URL string from parse_url-style parts using Guzzle PSR-7.
     * Decodes curly braces that Guzzle encodes to preserve Mautic tokens.
     *
     * @param array<string, mixed> $parts
     */
    private function httpBuildUrl(array $parts): string
    {
        $uri = (string) Uri::fromParts($parts);

        // Decode curly braces that Guzzle encoded to preserve Mautic tokens like {contactfield=bar}
        return str_replace(['%7B', '%7D'], ['{', '}'], $uri);
    }

    private function isContactFieldToken(string $token): bool
    {
        return str_contains($token, '{contactfield') || str_contains($token, '{leadfield') || str_contains($token, '{ownerfield');
    }

    /**
     * @param array<int|string, Redirect|Trackable> $trackableTokens
     */
    private function parseContent(string $content, ?string $channel, ?int $channelId, array &$trackableTokens): string
    {
        $this->hasFirstPassReplacements = false;

        // Reset content replacement arrays
        $this->contentReplacements = [
            'first_pass'  => [],
            'second_pass' => [],
        ];

        $trackableUrls = $this->extractTrackablesFromContent($content);
        $contentType   = (preg_match('/<(.*?) href/i', $content)) ? 'html' : 'text';
        if (count($trackableUrls)) {
            // Create Trackable/Redirect entities for the URLs
            $entities = $this->getEntitiesFromUrls($trackableUrls, $channel, $channelId);
            unset($trackableUrls);

            // Get a list of url => token to return to calling method and also to be used to
            // replace the urls in the content with tokens
            $trackableTokens = array_merge(
                $trackableTokens,
                $this->createTrackingTokens($entities)
            );

            unset($entities);

            // Replace URLs in content with tokens
            $content = $this->prepareContentWithTrackableTokens($content, $contentType);
        } elseif ($this->hasFirstPassReplacements) {
            // Apply first-pass replacements even when no trackables are created.
            $content = $this->prepareContentWithTrackableTokens($content, $contentType);
        }

        return $content;
    }

    private function getContactFieldUrlTokens(): array
    {
        if (null !== $this->contactFieldUrlTokens) {
            return $this->contactFieldUrlTokens;
        }

        $this->contactFieldUrlTokens = [];

        $fieldEntities = $this->leadFieldRepository->getFieldsByType('url');
        foreach ($fieldEntities as $field) {
            $this->contactFieldUrlTokens[] = $field->getAlias();
        }

        $this->leadFieldRepository->detachEntities($fieldEntities);

        return $this->contactFieldUrlTokens;
    }

    /**
     * @param \DOMNodeList<\DOMNode> $links
     *
     * @return array<string, string>
     */
    private function extractTrackables(\DOMNodeList $links): array
    {
        $trackableUrls = [];
        /** @var \DOMElement $link */
        foreach ($this->extractHrefs($links) as $link) {
            $url = $link->getAttribute('href');

            // Check for a do not track in proper HTML format
            if ($link->hasAttribute('data-mautic-disable-tracking') && 'true' === $link->getAttribute('data-mautic-disable-tracking')) {
                $this->doNotTrack[$url] = $url;
                continue;
            }

            if ($preparedUrl = $this->prepareUrlForTracking($url)) {
                [$urlKey, $urlValue]     = $preparedUrl;
                $trackableUrls[$urlKey]  = $urlValue;
            }
        }

        return $trackableUrls;
    }

    /**
     * @return \Generator<int, \DOMElement>
     */
    private function extractHrefs(\DOMNodeList $elements): \Generator
    {
        /** @var \DOMElement $element */
        foreach ($elements as $element) {
            $url = $element->getAttribute('href');

            if ('' === $url) {
                continue;
            }

            yield $element;
        }
    }
}
