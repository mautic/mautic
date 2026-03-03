<?php

namespace Mautic\PageBundle\Model;

use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Psr7\Uri;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\UrlHelper;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Model\AbstractCommonModel;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\LeadBundle\Entity\LeadFieldRepository;
use Mautic\LeadBundle\Helper\TokenHelper;
use Mautic\PageBundle\Entity\Redirect;
use Mautic\PageBundle\Entity\Trackable;
use Mautic\PageBundle\Event\UntrackableUrlsEvent;
use Mautic\PageBundle\PageEvents;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * @extends AbstractCommonModel<Trackable>
 */
class TrackableModel extends AbstractCommonModel
{
    /**
     * Array of URLs and/or tokens that should not be converted to trackables.
     *
     * @var array
     */
    protected $doNotTrack = [];

    /**
     * Tokens with values that could be used as URLs.
     *
     * @var array
     */
    protected $contentTokens = [];

    /**
     * Stores content that needs to be replaced when URLs are parsed out of content.
     *
     * @var array
     */
    protected $contentReplacements = [];

    /**
     * Used to rebuild correct URLs when the tokenized URL contains query parameters.
     *
     * @var bool
     */
    protected $usingClickthrough = true;

    private ?array $contactFieldUrlTokens = null;

    public function __construct(
        protected RedirectModel $redirectModel,
        private LeadFieldRepository $leadFieldRepository,
        EntityManagerInterface $em,
        CorePermissions $security,
        EventDispatcherInterface $dispatcher,
        UrlGeneratorInterface $router,
        Translator $translator,
        UserHelper $userHelper,
        LoggerInterface $mauticLogger,
        CoreParametersHelper $coreParametersHelper,
    ) {
        parent::__construct($em, $security, $dispatcher, $router, $translator, $userHelper, $mauticLogger, $coreParametersHelper);
    }

    /**
     * @return \Mautic\PageBundle\Entity\TrackableRepository
     */
    public function getRepository()
    {
        return $this->em->getRepository(Trackable::class);
    }

    /**
     * @return RedirectModel
     */
    protected function getRedirectModel()
    {
        return $this->redirectModel;
    }

    /**
     * @param array      $clickthrough
     * @param bool|false $shortenUrl   If true, use the configured shortener service to shorten the URLs
     * @param array      $utmTags
     *
     * @return string
     */
    public function generateTrackableUrl(
        Trackable $trackable,
        $clickthrough = [],
        $shortenUrl = false,
        $utmTags = [],
    ) {
        if (!isset($clickthrough['channel'])) {
            $clickthrough['channel'] = [$trackable->getChannel() => $trackable->getChannelId()];
        }

        $redirect = $trackable->getRedirect();

        $redirectModel = $this->getRedirectModel();

        $trackedUrl = $redirectModel->generateRedirectUrl($redirect, $clickthrough);

        if ([] !== $utmTags) {
            $trackedUrl = $redirectModel->applyUtmTags($trackedUrl, $utmTags);
        }

        if ($shortenUrl) {
            $trackedUrl = $redirectModel->shortenUrl($trackedUrl);
        }

        return $trackedUrl;
    }

    /**
     * Return a channel Trackable entity by URL.
     *
     * @return Trackable|null
     */
    public function getTrackableByUrl($url, $channel, $channelId)
    {
        if (empty($url)) {
            return null;
        }

        // Ensure the URL saved to the database does not have encoded ampersands
        $url = UrlHelper::decodeAmpersands($url);

        $trackable = $this->getRepository()->findByUrl($url, $channel, $channelId);
        if (null == $trackable) {
            $trackable = $this->createTrackableEntity($url, $channel, $channelId);
            $this->getRepository()->saveEntity($trackable->getRedirect());
            $this->getRepository()->saveEntity($trackable);
        }

        return $trackable;
    }

    /**
     * Get Trackable entities by an array of URLs.
     *
     * @return array<Trackable>
     */
    public function getTrackablesByUrls($urls, $channel, $channelId)
    {
        $uniqueUrls = array_unique(
            array_values($urls)
        );

        $trackables = $this->getRepository()->findByUrls(
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
            $this->getRepository()->saveEntities($newRedirects);
        }
        if (count($newTrackables)) {
            $this->getRepository()->saveEntities($newTrackables);
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
        return $this->getRepository()->findByChannel($channel, $channelId);
    }

    /**
     * Returns a list of tokens and/or URLs that should not be converted to trackables.
     *
     * @param string|string[]|null $content
     */
    public function getDoNotTrackList($content): array
    {
        /** @var UntrackableUrlsEvent $event */
        $event = $this->dispatcher->dispatch(
            new UntrackableUrlsEvent($content),
            PageEvents::REDIRECT_DO_NOT_TRACK
        );

        return $event->getDoNotTrackList();
    }

    /**
     * Extract URLs from content and return as trackables.
     *
     * @param string|string[] $content
     * @param string[]        $contentTokens
     * @param ?string         $channel
     * @param ?int            $channelId
     * @param bool            $usingClickthrough Set to false if not using a clickthrough parameter.
     *                                           This is to ensure that URLs are built correctly with ? or & for
     *                                           URLs tracked that include query parameters
     *
     * @return array{string|string[],Redirect[]|Trackable[]}
     */
    public function parseContentForTrackables($content, array $contentTokens = [], $channel = null, $channelId = null, $usingClickthrough = true): array
    {
        $this->usingClickthrough = $usingClickthrough;

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
    protected function createTrackingTokens(array $entities): array
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
     * @param string $content
     * @param string $type    html|text
     *
     * @return string
     */
    protected function prepareContentWithTrackableTokens($content, $type)
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
     * @return array
     */
    protected function extractTrackablesFromContent($content)
    {
        if (0 !== preg_match('/<[^<]+>/', $content)) {
            // Parse as HTML
            $trackableUrls = $this->extractTrackablesFromHtml($content);
        } else {
            // Parse as plain text
            $trackableUrls = $this->extractTrackablesFromText($content);
        }

        return $trackableUrls;
    }

    /**
     * Find URLs in HTML and parse into trackables.
     *
     * @param string $html HTML content
     */
    protected function extractTrackablesFromHtml($html): array
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
    protected function extractTrackablesFromText($text): array
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

    /**
     * Create a Trackable entity.
     */
    protected function createTrackableEntity($url, $channel, $channelId): Trackable
    {
        $redirect = $this->getRedirectModel()->createRedirectEntity($url);

        $trackable = new Trackable();
        $trackable->setChannel($channel)
            ->setChannelId($channelId)
            ->setRedirect($redirect);

        return $trackable;
    }

    /**
     * Validate and parse link for tracking.
     *
     * @return bool|non-empty-array<mixed, mixed>
     */
    protected function prepareUrlForTracking(string $url)
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
                $this->contentReplacements['first_pass'][$url] = $trackableUrl;
            }
        } else {
            // Regular URL without a tokenized host
            $trackableUrl = $this->httpBuildUrl($urlParts);

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
    protected function isInDoNotTrack($url): bool
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
    protected function validateTokenIsTrackable($token, $tokenizedHost = null): bool
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

        if (!$this->isValidUrl($tokenValue)) {
            return false;
        }

        return true;
    }

    /**
     * @param bool $forceScheme
     */
    protected function isValidUrl($url, $forceScheme = true): bool
    {
        $urlParts = (!is_array($url)) ? parse_url($url) : $url;

        // Ensure a applicable URL (rule out URLs as just #)
        if (!isset($urlParts['host']) && !isset($urlParts['path'])) {
            return false;
        }

        // Ensure a valid scheme
        if (($forceScheme && !isset($urlParts['scheme']))
            || (isset($urlParts['scheme'])
                && !in_array(
                    $urlParts['scheme'],
                    ['http', 'https', 'ftp', 'ftps', 'mailto']
                ))) {
            return false;
        }

        return true;
    }

    /**
     * Find and extract tokens from the URL as this have to be processed outside of tracking tokens.
     *
     * @param $urlParts Array from parse_url
     *
     * @return array|false
     */
    protected function extractTokensFromQuery(&$urlParts)
    {
        $tokenizedParams = false;

        // Check for a token with a query appended such as {pagelink=1}&key=value
        if (isset($urlParts['path']) && preg_match('/([https?|ftps?]?\{.*?\})&(.*?)$/', $urlParts['path'], $match)) {
            $urlParts['path'] = $match[1];
            if (isset($urlParts['query'])) {
                // Likely won't happen but append if this exists
                $urlParts['query'] .= '&'.$match[2];
            } else {
                $urlParts['query'] = $match[2];
            }
        }

        // Check for tokens in the query
        if (!empty($urlParts['query'])) {
            [$tokenizedParams, $untokenizedParams] = $this->parseTokenizedQuery($urlParts['query']);
            if ($tokenizedParams) {
                // Rebuild the query without the tokenized query params for now
                $urlParts['query'] = $this->httpBuildQuery($untokenizedParams);
            }
        }

        return $tokenizedParams;
    }

    /**
     * Group query parameters into those that have tokens and those that do not.
     *
     * @return array<array<string, mixed>> [$tokenizedParams[], $untokenizedParams[]]
     */
    protected function parseTokenizedQuery($query): array
    {
        $tokenizedParams   =
        $untokenizedParams = [];

        // Test to see if there are tokens in the query and if so, extract and append them to the end of the tracked link
        if (preg_match('/(\{\S+?\})/', $query)) {
            // Equal signs in tokens will confuse parse_str so they need to be encoded
            $query = preg_replace('/\{(\S+?)=(\S+?)\}/', '{$1%3D$2}', $query);

            parse_str($query, $queryParts);

            foreach ($queryParts as $key => $value) {
                if (preg_match('/(\{\S+?\})/', $key) || preg_match('/(\{\S+?\})/', $value)) {
                    $tokenizedParams[$key] = $value;
                } else {
                    $untokenizedParams[$key] = $value;
                }
            }
        }

        return [$tokenizedParams, $untokenizedParams];
    }

    /**
     * @return array<string, Trackable|Redirect>
     */
    protected function getEntitiesFromUrls($trackableUrls, $channel, $channelId)
    {
        if (!empty($channel) && !empty($channelId)) {
            // Track as channel aware
            return $this->getTrackablesByUrls($trackableUrls, $channel, $channelId);
        }

        // Simple redirects
        return $this->getRedirectModel()->getRedirectsByUrls($trackableUrls);
    }

    /**
     * Build a URL string from parse_url-style parts using Guzzle PSR-7.
     * Decodes curly braces that Guzzle encodes to preserve Mautic tokens.
     *
     * @param array<string, mixed> $parts
     */
    protected function httpBuildUrl(array $parts): string
    {
        $uri = (string) Uri::fromParts($parts);

        // Decode curly braces that Guzzle encoded to preserve Mautic tokens like {contactfield=bar}
        return str_replace(['%7B', '%7D'], ['{', '}'], $uri);
    }

    /**
     * Build query string while accounting for tokens that include an equal sign.
     *
     * @return mixed|string
     */
    protected function httpBuildQuery(array $queryParts)
    {
        $query = http_build_query($queryParts);

        // http_build_query likely encoded tokens so that has to be fixed so they get replaced
        $query = preg_replace_callback(
            '/%7B(\S+?)%7D/i',
            fn ($matches): string => urldecode($matches[0]),
            $query
        );

        return $query;
    }

    private function isContactFieldToken($token): bool
    {
        return str_contains($token, '{contactfield') || str_contains($token, '{leadfield');
    }

    /**
     * @param array<int|string, Redirect|Trackable> $trackableTokens
     *
     * @return string
     */
    private function parseContent($content, $channel, $channelId, array &$trackableTokens)
    {
        // Reset content replacement arrays
        $this->contentReplacements = [
            // PHPSTAN reported duplicate keys in this array. I can't determine which is the right one.
            // I'm leaving the second one to keep current behaviour but leaving the first one commented
            // out as it may be the one we want.
            // 'first_pass'  => [
            //     // Remove internal attributes
            //     // Editor may convert to HTML4
            //     'mautic:disable-tracking=""' => '',
            //     // HTML5
            //     'mautic:disable-tracking'    => '',
            // ],
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
        } elseif (!empty($this->contentReplacements['first_pass'])) {
            // Replace URLs in content with tokens
            $content = $this->prepareContentWithTrackableTokens($content, $contentType);
        }

        return $content;
    }

    /**
     * @return array
     */
    protected function getContactFieldUrlTokens()
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

            // Check for a do not track
            // @deprecated since 7.x — Will be removed in 8.0. Use data-mautic-disable-tracking.
            if ($link->hasAttribute('mautic:disable-tracking')) {
                $this->doNotTrack[$url] = $url;
                continue;
            }

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
