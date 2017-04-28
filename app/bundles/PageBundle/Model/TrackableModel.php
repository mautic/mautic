<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PageBundle\Model;

use Mautic\CoreBundle\Model\AbstractCommonModel;
use Mautic\PageBundle\Entity\Redirect;
use Mautic\PageBundle\Entity\Trackable;
use Mautic\PageBundle\Event\UntrackableUrlsEvent;
use Mautic\PageBundle\PageEvents;

/**
 * Class TrackableModel.
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

    /**
     * @var RedirectModel
     */
    protected $redirectModel;

    /**
     * TrackableModel constructor.
     *
     * @param RedirectModel $redirectModel
     */
    public function __construct(RedirectModel $redirectModel)
    {
        $this->redirectModel = $redirectModel;
    }

    /**
     * {@inheritdoc}
     *
     * @return \Mautic\PageBundle\Entity\TrackableRepository
     */
    public function getRepository()
    {
        return $this->em->getRepository('MauticPageBundle:Trackable');
    }

    /**
     * @return RedirectModel
     */
    protected function getRedirectModel()
    {
        return $this->redirectModel;
    }

    /**
     * @param Trackable  $trackable
     * @param array      $clickthrough
     * @param bool|false $shortenUrl   If true, use the configured shortener service to shorten the URLs
      @param array      $utmTags
     *
     * @return string
     */
    public function generateTrackableUrl(Trackable $trackable, $clickthrough = [], $shortenUrl = false, $utmTags = [])
    {
        if (!isset($clickthrough['channel'])) {
            $clickthrough['channel'] = [$trackable->getChannel() => $trackable->getChannelId()];
        }

        $redirect = $trackable->getRedirect();

        return $this->getRedirectModel()->generateRedirectUrl($redirect, $clickthrough, $shortenUrl, $utmTags);
    }

    /**
     * Return a channel Trackable entity by URL.
     *
     * @param   $url
     * @param   $channel
     * @param   $channelId
     *
     * @return Trackable|null
     */
    public function getTrackableByUrl($url, $channel, $channelId)
    {
        if (empty($url)) {
            return null;
        }

        // Ensure the URL saved to the database does not have encoded ampersands
        while (strpos($url, '&amp;') !== false) {
            $url = str_replace('&amp;', '&', $url);
        }

        $trackable = $this->getRepository()->findByUrl($url, $channel, $channelId);
        if ($trackable == null) {
            $trackable = $this->createTrackableEntity($url, $channel, $channelId);
            $this->getRepository()->saveEntity($trackable->getRedirect());
            $this->getRepository()->saveEntity($trackable);
        }

        return $trackable;
    }

    /**
     * Get Trackable entities by an array of URLs.
     *
     * @param $urls
     * @param $channel
     * @param $channelId
     *
     * @return array
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
        $return        = [];
        $byUrl         = [];

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
     * @param $channel
     * @param $channelId
     *
     * @return mixed
     */
    public function getTrackableList($channel, $channelId)
    {
        return $this->getRepository()->findByChannel($channel, $channelId);
    }

    /**
     * Returns a list of tokens and/or URLs that should not be converted to trackables.
     *
     * @param null $content
     *
     * @return array
     */
    public function getDoNotTrackList($content = null)
    {
        $event = $this->dispatcher->dispatch(
            PageEvents::REDIRECT_DO_NOT_TRACK,
            new UntrackableUrlsEvent($content)
        );

        return $event->getDoNotTrackList();
    }

    /**
     * Extract URLs from content and return as trackables.
     *
     * @param mixed      $content
     * @param array      $contentTokens
     * @param null       $channel
     * @param null       $channelId
     * @param bool|false $usingClickthrough Set to false if not using a clickthrough parameter. This is to ensure that URLs are built correctly with ?
     *                                      or & for URLs tracked that include query parameters
     *
     * @return array[mixed $content, array $trackables]
     */
    public function parseContentForTrackables($content, array $contentTokens = [], $channel = null, $channelId = null, $usingClickthrough = true)
    {
        $this->usingClickthrough = $usingClickthrough;

        // Reset content replacement arrays
        $this->contentReplacements = [
            'first_pass' => [
                // Remove internal attributes
                // Editor may convert to HTML4
                'mautic:disable-tracking=""' => '',
                // HTML5
                'mautic:disable-tracking' => '',
            ],
            'first_pass'  => [],
            'second_pass' => [],
        ];

        // Set do not track list for validateUrlIsTrackable()
        $this->doNotTrack = $this->getDoNotTrackList($content);

        // Set content tokens used by validateUrlIsTrackable()
        $this->contentTokens = $contentTokens;

        $trackableUrls    = [];
        $trackableTokens  = [];
        $contentWasString = false;
        if (!is_array($content)) {
            $contentWasString = true;
            $content          = [$content];
        }

        foreach ($content as &$text) {
            if (preg_match('/<a(.*?) href/i', $text)) {
                // Parse as HTML
                $trackableUrls = array_merge(
                    $trackableUrls,
                    $this->extractTrackablesFromHtml($text)
                );
            } else {
                // Parse as plain text
                $trackableUrls = array_merge(
                    $trackableUrls,
                    $this->extractTrackablesFromText($text)
                );
            }
        }

        if (count($trackableUrls)) {
            // Create Trackable/Redirect entities for the URLs
            $entities = $this->getEntitiesFromUrls($trackableUrls, $channel, $channelId);
            unset($trackableUrls);

            // Get a list of url => token to return to calling method and also to be used to
            // replace the urls in the content with tokens
            $trackableTokens = $this->createTrackingTokens($entities);
            unset($entities);

            // Replace URLs in content with tokens
            foreach ($content as &$text) {
                $type = (preg_match('/<a(.*?) href/i', $text)) ? 'html' : 'text';
                $text = $this->prepareContentWithTrackableTokens($text, $type);
            }
        } elseif (!empty($this->contentReplacements['first_pass'])) {
            // Replace URLs in content with tokens
            foreach ($content as &$text) {
                $type = (preg_match('/<a(.*?) href/i', $text)) ? 'html' : 'text';
                $text = $this->prepareContentWithTrackableTokens($text, $type);
            }
        }

        return [
            $contentWasString ? $content[0] : $content,
            $trackableTokens,
        ];
    }

    /**
     * Converts array of Trackable or Redirect entities into {trackable} tokens.
     *
     * @param array $entities
     *
     * @return array
     */
    protected function createTrackingTokens(array $entities)
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
        krsort($this->contentReplacements['second_pass']);

        if ('html' == $type) {
            // For HTML, replace only the links; leaving the link text (if a URL) intact
            foreach ($this->contentReplacements['second_pass'] as $search => $replace) {
                $content = preg_replace(
                    '/<a(.*?) href=(["\'])'.preg_quote($search, '/').'(.*?)\\2(.*?)>/i',
                    '<a$1 href=$2'.$replace.'$3$2$4>',
                    $content
                );
            }
        } else {
            // For text, just do a simple search/replace
            $secondPassSearch  = array_keys($this->contentReplacements['second_pass']);
            $secondPassReplace = $this->contentReplacements['second_pass'];
            $content           = str_ireplace($secondPassSearch, $secondPassReplace, $content);
        }

        unset($firstSearch, $firstReplace, $secondSearch, $secondSearch);

        return $content;
    }

    /**
     * Find URLs in HTML and parse into trackables.
     *
     * @param string $html HTML content
     *
     * @return array
     */
    protected function extractTrackablesFromHtml($html)
    {
        // Find links using DOM to only find <a> tags
        $libxmlPreviousState = libxml_use_internal_errors(true);
        libxml_use_internal_errors(true);
        $dom = new \DOMDocument();
        $dom->loadHTML('<?xml encoding="UTF-8">'.$html);
        libxml_clear_errors();
        libxml_use_internal_errors($libxmlPreviousState);
        $links = $dom->getElementsByTagName('a');

        $trackableUrls = [];

        /** @var \DOMElement $link */
        foreach ($links as $link) {
            $url = $link->getAttribute('href');

            // Check for a do not track
            if ($link->hasAttribute('mautic:disable-tracking')) {
                $this->doNotTrack[$url] = $url;

                continue;
            }

            if ($preparedUrl = $this->prepareUrlForTracking($url)) {
                list($urlKey, $urlValue) = $preparedUrl;
                $trackableUrls[$urlKey]  = $urlValue;
            }
        }

        return $trackableUrls;
    }

    /**
     * Find URLs in plain text and parse into trackables.
     *
     * @param string $text Plain text content
     *
     * @return array
     */
    protected function extractTrackablesFromText($text)
    {
        // Remove any HTML tags (such as img) that could contain href or src attributes prior to parsing for links
        $text = strip_tags($text);

        // Plaintext links
        $trackableUrls = [];
        if (preg_match_all('/((https?|ftps?):\/\/)([a-zA-Z0-9-\.{}]*[a-zA-Z0-9=}]*)(\??)([^\s\]"]+)?/i', $text, $matches)) {
            foreach ($matches[0] as $url) {
                if ($preparedUrl = $this->prepareUrlForTracking($url)) {
                    list($urlKey, $urlValue) = $preparedUrl;
                    $trackableUrls[$urlKey]  = $urlValue;
                }
            }
        }

        // Any tokens could potentially be a URL so extract and send through  prepareUrlForTracking() which will determine
        // if it's a valid URL or not
        if (preg_match_all('/{.*?}/i', $text, $matches)) {
            foreach ($matches[0] as $url) {
                if ($preparedUrl = $this->prepareUrlForTracking($url)) {
                    list($urlKey, $urlValue) = $preparedUrl;
                    $trackableUrls[$urlKey]  = $urlValue;
                }
            }
        }

        return $trackableUrls;
    }

    /**
     * Create a Trackable entity.
     *
     * @param $url
     * @param $channel
     * @param $channelId
     *
     * @return Trackable
     */
    protected function createTrackableEntity($url, $channel, $channelId)
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
     * @param $url
     *
     * @return array[$trackingKey, $trackingUrl]|false
     */
    protected function prepareUrlForTracking($url)
    {
        // Ensure it's clean
        $url = trim($url);

        // Ensure these are & for the sake of parsing
        while (strpos($url, '&amp;') !== false) {
            $url = str_replace('&amp;', '&', $url);
        }

        // If this is just a token, validate it's supported before going further
        if (preg_match('/^{.*?}$/i', $url) && !$this->validateTokenIsTrackable($url)) {
            return false;
        }

        // Default key and final URL to the given $url
        $trackableKey = $trackableUrl = $url;

        // Convert URL
        $urlParts = parse_url($url);

        if (!$this->isValidUrl($urlParts, false)) {
            return false;
        }

        // Extract any tokens that are part of the query
        $tokenizedParams = $this->extractTokensFromQuery($urlParts);

        // Check if URL is trackable
        $tokenizedHost = (!isset($urlParts['host']) && isset($urlParts['path'])) ? $urlParts['path'] : $urlParts['host'];
        if (preg_match('/^(\{\S+?\})/', $tokenizedHost, $match)) {
            $token = $match[1];

            // Tokenized hosts shouldn't use a scheme since the token value should contain it
            if ($scheme = (!empty($urlParts['scheme'])) ? $urlParts['scheme'] : false) {
                // Token has a schema so let's get rid of it before replacing tokens
                $this->contentReplacements['first_pass'][$scheme.'://'.$tokenizedHost] = $tokenizedHost;
                unset($urlParts['scheme']);
            }

            // Validate that the token is something that can be trackable
            if (!$this->validateTokenIsTrackable($token, $tokenizedHost)) {
                return false;
            }

            $trackableUrl = (!empty($urlParts['query'])) ? $this->contentTokens[$token].'?'.$urlParts['query'] : $this->contentTokens[$token];
            $trackableKey = $trackableUrl;

            // Replace the URL token with the actual URL
            $this->contentReplacements['first_pass'][$url] = $trackableUrl;
        } else {
            // Regular URL without a tokenized host
            $trackableUrl = $this->httpBuildUrl($urlParts);

            if ($this->isInDoNotTrack($trackableUrl)) {
                return false;
            }
        }

        // Append tokenized params to the end of the URL as these will not be part of the stored redirect URL
        // They'll be passed through as regular parameters outside the trackable token
        // For example, {trackable=123}?foo={bar}
        if ($tokenizedParams) {
            // The URL to be tokenized is without the tokenized parameters
            $trackableKey = $trackableUrl.($this->usingClickthrough || (strpos($trackableUrl, '?') !== false) ? '&' : '?').
                $this->httpBuildQuery($tokenizedParams);

            // Replace the original URL with the updated URL before replacing with tokens
            if ($trackableKey !== $url) {
                $this->contentReplacements['first_pass'][$url] = $trackableKey;
            }
        }

        return [$trackableKey, $trackableUrl];
    }

    /**
     * Determines if a URL/token is in the do not track list.
     *
     * @param $url
     *
     * @return bool
     */
    protected function isInDoNotTrack($url)
    {
        // Ensure it's not in the do not track list
        foreach ($this->doNotTrack as $notTrackable) {
            if (preg_match('/'.preg_quote($notTrackable, '/').'/', $url)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Validates that a token is trackable as a URL.
     *
     * @param      $token
     * @param null $tokenizedHost
     *
     * @return bool
     */
    protected function validateTokenIsTrackable($token, $tokenizedHost = null)
    {
        // Token as URL
        if ($tokenizedHost && !preg_match('/^(\{\S+?\})$/', $tokenizedHost)) {
            // Currently this does not apply to something like "{leadfield=firstname}.com" since that could result in URL per lead

            return false;
        }

        // Validate if this token is listed as not to be tracked
        if ($this->isInDoNotTrack($token)) {
            return false;
        }

        // Validate that the token is available and is a URL
        if (!isset($this->contentTokens[$token]) || !$this->isValidUrl($this->contentTokens[$token])) {
            return false;
        }

        return true;
    }

    /**
     * @param      $url
     * @param bool $forceScheme
     *
     * @return bool
     */
    protected function isValidUrl($url, $forceScheme = true)
    {
        $urlParts = (!is_array($url)) ? parse_url($url) : $url;

        // Ensure a applicable URL (rule out URLs as just #)
        if (!isset($urlParts['host']) && !isset($urlParts['path'])) {
            return false;
        }

        // Ensure a valid scheme
        if (($forceScheme && !isset($urlParts['scheme'])) || (isset($urlParts['scheme']) && !in_array($urlParts['scheme'], ['http', 'https', 'ftp', 'ftps']))) {
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
            list($tokenizedParams, $untokenizedParams) = $this->parseTokenizedQuery($urlParts['query']);
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
     * @param $query
     *
     * @return array[$tokenizedParams[], $untokenizedParams[]]
     */
    protected function parseTokenizedQuery($query)
    {
        $tokenizedParams   =
        $untokenizedParams = [];

        // Test to see if there are tokens in the query and if so, extract and append them to the end of the tracked link
        if (preg_match('/(\{\S+?\})/', $query)) {
            // Equal signs in tokens will confuse parse_str so they need to be encoded
            $query = preg_replace('/\{(\S+?)=(\S+?)\}/', '{$1%3D$2}', $query);

            parse_str($query, $queryParts);

            if (is_array($queryParts)) {
                foreach ($queryParts as $key => $value) {
                    if (preg_match('/(\{\S+?\})/', $key) || preg_match('/(\{\S+?\})/', $value)) {
                        $tokenizedParams[$key] = $value;
                    } else {
                        $untokenizedParams[$key] = $value;
                    }
                }
            }
        }

        return [$tokenizedParams, $untokenizedParams];
    }

    /**
     * @param $trackableUrls
     * @param $channel
     * @param $channelId
     *
     * @return array
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
     * @param $parts
     *
     * @return string
     */
    protected function httpBuildUrl($parts)
    {
        if (function_exists('http_build_url')) {
            return http_build_url($parts);
        } else {
            /*
             * Used if extension is not installed
             *
             * http_build_url
             * Stand alone version of http_build_url (http://php.net/manual/en/function.http-build-url.php)
             * Based on buggy and inefficient version I found at http://www.mediafire.com/?zjry3tynkg5 by tycoonmaster[at]gmail[dot]com
             *
             * @author    Chris Nasr (chris[at]fuelforthefire[dot]ca)
             * @copyright Fuel for the Fire
             * @package   http
             * @version   0.1
             * @created   2012-07-26
             */

            if (!defined('HTTP_URL_REPLACE')) {
                // Define constants
                define('HTTP_URL_REPLACE', 0x0001);    // Replace every part of the first URL when there's one of the second URL
                define('HTTP_URL_JOIN_PATH', 0x0002);    // Join relative paths
                define('HTTP_URL_JOIN_QUERY', 0x0004);    // Join query strings
                define('HTTP_URL_STRIP_USER', 0x0008);    // Strip any user authentication information
                define('HTTP_URL_STRIP_PASS', 0x0010);    // Strip any password authentication information
                define('HTTP_URL_STRIP_PORT', 0x0020);    // Strip explicit port numbers
                define('HTTP_URL_STRIP_PATH', 0x0040);    // Strip complete path
                define('HTTP_URL_STRIP_QUERY', 0x0080);    // Strip query string
                define('HTTP_URL_STRIP_FRAGMENT', 0x0100);    // Strip any fragments (#identifier)

                // Combination constants
                define('HTTP_URL_STRIP_AUTH', HTTP_URL_STRIP_USER | HTTP_URL_STRIP_PASS);
                define('HTTP_URL_STRIP_ALL', HTTP_URL_STRIP_AUTH | HTTP_URL_STRIP_PORT | HTTP_URL_STRIP_QUERY | HTTP_URL_STRIP_FRAGMENT);
            }

            $flags = HTTP_URL_REPLACE;
            $url   = [];

            // Scheme and Host are always replaced
            if (isset($parts['scheme'])) {
                $url['scheme'] = $parts['scheme'];
            }
            if (isset($parts['host'])) {
                $url['host'] = $parts['host'];
            }

            // (If applicable) Replace the original URL with it's new parts
            if (HTTP_URL_REPLACE & $flags) {
                // Go through each possible key
                foreach (['user', 'pass', 'port', 'path', 'query', 'fragment'] as $key) {
                    // If it's set in $parts, replace it in $url
                    if (isset($parts[$key])) {
                        $url[$key] = $parts[$key];
                    }
                }
            } else {
                // Join the original URL path with the new path
                if (isset($parts['path']) && (HTTP_URL_JOIN_PATH & $flags)) {
                    if (isset($url['path']) && $url['path'] != '') {
                        // If the URL doesn't start with a slash, we need to merge
                        if ($url['path'][0] != '/') {
                            // If the path ends with a slash, store as is
                            if ('/' == $parts['path'][strlen($parts['path']) - 1]) {
                                $sBasePath = $parts['path'];
                            } // Else trim off the file
                            else {
                                // Get just the base directory
                                $sBasePath = dirname($parts['path']);
                            }

                            // If it's empty
                            if ('' == $sBasePath) {
                                $sBasePath = '/';
                            }

                            // Add the two together
                            $url['path'] = $sBasePath.$url['path'];

                            // Free memory
                            unset($sBasePath);
                        }

                        if (false !== strpos($url['path'], './')) {
                            // Remove any '../' and their directories
                            while (preg_match('/\w+\/\.\.\//', $url['path'])) {
                                $url['path'] = preg_replace('/\w+\/\.\.\//', '', $url['path']);
                            }

                            // Remove any './'
                            $url['path'] = str_replace('./', '', $url['path']);
                        }
                    } else {
                        $url['path'] = $parts['path'];
                    }
                }

                // Join the original query string with the new query string
                if (isset($parts['query']) && (HTTP_URL_JOIN_QUERY & $flags)) {
                    if (isset($url['query'])) {
                        $url['query'] .= '&'.$parts['query'];
                    } else {
                        $url['query'] = $parts['query'];
                    }
                }
            }

            // Strips all the applicable sections of the URL
            if (HTTP_URL_STRIP_USER & $flags) {
                unset($url['user']);
            }
            if (HTTP_URL_STRIP_PASS & $flags) {
                unset($url['pass']);
            }
            if (HTTP_URL_STRIP_PORT & $flags) {
                unset($url['port']);
            }
            if (HTTP_URL_STRIP_PATH & $flags) {
                unset($url['path']);
            }
            if (HTTP_URL_STRIP_QUERY & $flags) {
                unset($url['query']);
            }
            if (HTTP_URL_STRIP_FRAGMENT & $flags) {
                unset($url['fragment']);
            }

            // Combine the new elements into a string and return it
            return
                ((isset($url['scheme'])) ? $url['scheme'].'://' : '')
                .((isset($url['user'])) ? $url['user'].((isset($url['pass'])) ? ':'.$url['pass'] : '').'@' : '')
                .((isset($url['host'])) ? $url['host'] : '')
                .((isset($url['port'])) ? ':'.$url['port'] : '')
                .((isset($url['path'])) ? $url['path'] : '')
                .((!empty($url['query'])) ? '?'.$url['query'] : '')
                .((!empty($url['fragment'])) ? '#'.$url['fragment'] : '');
        }
    }

    /**
     * Build query string while accounting for tokens that include an equal sign.
     *
     * @param array $queryParts
     *
     * @return mixed|string
     */
    protected function httpBuildQuery(array $queryParts)
    {
        $query = http_build_query($queryParts);

        // http_build_query likely encoded tokens so that has to be fixed so they get replaced
        $query = preg_replace_callback(
            '/%7B(\S+?)%7D/i',
            function ($matches) {
                return urldecode($matches[0]);
            },
            $query
        );

        return $query;
    }
}
