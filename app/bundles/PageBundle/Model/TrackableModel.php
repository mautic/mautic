<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PageBundle\Model;

use Mautic\CoreBundle\Model\CommonModel;
use Mautic\PageBundle\Entity\Redirect;
use Mautic\PageBundle\Entity\Trackable;
use Mautic\PageBundle\Event\UntrackableUrlsEvent;
use Mautic\PageBundle\PageEvents;

/**
 * Class TrackableModel
 */
class TrackableModel extends CommonModel
{
    /**
     * Array of URLs and/or tokens that should not be converted to trackables
     *
     * @var array
     */
    protected $doNotTrack = array();

    /**
     * Tokens with values that could be used as URLs
     *
     * @var array
     */
    protected $contentTokens = array();

    /**
     * Stores content that needs to be replaced when URLs are parsed out of content
     *
     * @var array
     */
    protected $contentReplacements = array();

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
        return $this->factory->getModel('page.redirect');
    }

    /**
     * @param Trackable $trackable
     * @param array     $clickthrough
     *
     * @return string
     */
    public function generateTrackableUrl(Trackable $trackable, $clickthrough = array())
    {
        if (!isset($clickthrough['channel'])) {
            $clickthrough['channel']    = $trackable->getChannel();
            $clickthrough['channel_id'] = $trackable->getChannelId();
        }

        $redirect = $trackable->getRedirect();

        return $this->getRedirectModel()->generateRedirectUrl($redirect, $clickthrough);;
    }

    /**
     * Return a channel Trackable entity by URL
     *
     * @param      $url
     * @param      $channel
     * @param      $channelId
     *
     * @return Trackable|null
     */
    public function getTrackableByUrl($url, $channel, $channelId)
    {
        if (empty($url)) {

            return null;
        }

        // Ensure the URL saved to the database does not have encoded ampersands
        $url = str_replace('&amp;', '&', $url);

        $trackable = $this->getRepository()->findByUrl($url, $channel, $channelId);
        if ($trackable == null) {
            $trackable = $this->createTrackableEntity($url, $channel, $channelId);
            $this->getRepository()->saveEntity($trackable);
        }

        return $trackable;
    }

    /**
     * Get Trackable entities by an array of URLs
     *
     * @param $urls
     * @param $channel
     * @param $channelId
     *
     * @return array
     */
    public function getTrackablesByUrls($urls, $channel, $channelId)
    {
        $trackables  = $this->getRepository()->findByUrls(array_values($urls), $channel, $channelId);
        $newEntities = array();
        $return      = array();
        $byUrl       = array();

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
                $trackable     = $this->createTrackableEntity($url, $channel, $channelId);
                $newEntities[] = $trackable;
                $return[$key]  = $trackable;
            }
        }

        // Save new entities
        if (count($newEntities)) {
            $this->getRepository()->saveEntities($newEntities);
        }

        unset($trackables, $newEntities, $byUrl);

        return $return;
    }

    /**
     * Get a list of URLs that are tracked by a specific channel
     *
     * @param $channel
     * @param $channelId
     *
     * @return mixed
     */
    public function getTrackables($channel, $channelId)
    {
        return $this->getRepository()->findByChannel($channel, $channelId);
    }

    /**
     * Returns a list of tokens and/or URLs that should not be converted to trackables
     *
     * @param null $content
     *
     * @return array
     */
    public function getDoNotTrackList($content = null)
    {
        $event = $this->dispatcher->dispatch(
            PageEvents::REDIRECT_DO_NOT_TRACK,
            new UntrackableUrlsEvent()
        );

        return $event->getDoNotTrackList($content);
    }

    /**
     * Extract URLs from content and return as trackables
     *
     * @param       $content
     * @param array $contentTokens
     * @param null  $channel
     * @param null  $channelId
     *
     * @return array
     */
    public function parseContentForTrackables($content, array $contentTokens = array(), $channel = null, $channelId = null)
    {
        // Set do not track list for validateUrlIsTrackable()
        $this->doNotTrack  = $this->getDoNotTrackList($content);

        // Set content tokens used by validateUrlIsTrackable()
        $this->contentTokens = $contentTokens;

        $trackableUrls = array();
        if (!is_array($content)) {
            $content = array($content);
        }

        foreach ($content as &$text) {
            if (preg_match('/.*?<a.*?href.*?/i', $text)) {
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

        // Create Trackable/Redirect entities for the URLs
        $entities = $this->getEntitiesFromUrls($trackableUrls, $channel, $channelId);
        unset($trackableUrls);

        // Get a list of url => token
        $trackableTokens = $this->createTrackingTokens($entities);
        unset($entities);

        // Replace URLs with tokens
        $content = $this->prepareContentWithTrackableTokens($content);

        return array(
            explode($separator, $content),
            $trackableTokens
        );
    }

    /**
     * Converts array of Trackable or Redirect entities into {trackedlink} tokens
     *
     * @param array $entities
     *
     * @return array
     */
    protected function createTrackingTokens(array $entities)
    {
        $tokens = array();
        foreach ($entities as $url => $trackable) {
            $redirect       = ($trackable instanceof Trackable) ? $trackable->getRedirect() : $trackable;
            $token          = '{trackedlink='.$redirect->getRedirectId().'}';
            $tokens[$token] = $trackable;

            // Store the URL to be replaced by a token
            $this->contentReplacements['secondPass'][$url] = $token;
        }

        return $tokens;
    }

    /**
     * Prepares content for tokenized trackable URLs by replacing them with {trackedlink=ID} tokens
     */
    protected function prepareContentWithTrackableTokens($content, $type)
    {
        // Sort longer to shorter strings to ensure that URLs that share the same base are appropriately replaced
        krsort($this->contentReplacements['secondPass']);

        $firstSearch   = array_keys($this->contentReplacements['firstPass']);
        $firstReplace  = $this->contentReplacements['firstPass'];
        $secondSearch  = array_keys($this->contentReplacements['secondPass']);
        $secondReplace = $this->contentReplacements['secondPass'];

        // Remove tracking tags from content
        $firstSearch[]  = 'mautic:disable-tracking=""'; // Editor may convert to HTML4
        $firstSearch[]  = 'mautic:disable-tracking'; // HTML5
        $firstReplace[] = '';
        $firstReplace[] = '';

        // Prepare for token replacements with first pass
        $content = str_ireplace($firstSearch, $firstReplace, $content);

        // For HTML, replace only the links; leaving the link text (if a URL) intact
        foreach ($this->contentReplacements['secondPass'] as $search => $replace) {
            $content = preg_replace(
                '/<a(.*?) href=(["\'])'.preg_quote($search, '/').'(.*?)\\2(.*?)>/i',
                '<a$1 href=$2'.$replace.'$3$2$4>',
                $content
            );
        }

        $plainText = str_ireplace($secondSearch, $secondReplace, $plainText);

        // Regular search/replace to prepare for the second pass
        $content = str_ireplace($firstSearch, $firstReplace, $content);



        unset($firstSearch, $firstReplace, $secondSearch, $secondSearch, $content, $plainText);
    }

    /**
     * Find URLs in HTML and parse into trackables
     *
     * @param  $html HTML content
     *
     * @return array
     */
    protected function extractTrackablesFromHtml($html)
    {
        // Find links using DOM to only find <a> tags
        $libxmlPreviousState = libxml_use_internal_errors(true);
        libxml_use_internal_errors(true);
        $dom = new \DOMDocument;
        $dom->loadHTML('<?xml encoding="UTF-8">'.$html);
        libxml_clear_errors();
        libxml_use_internal_errors($libxmlPreviousState);
        $links = $dom->getElementsByTagName('a');

        $trackableUrls = array();

        /** @var \DOMElement $link */
        foreach ($links as $link) {
            $url = $link->getAttribute('href');

            // The editor will have converted & to &amp; but DOMDocument will have converted them back so this must be accounted for
            $url = str_replace('&', '&amp;', $url);

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
     * Find URLs in plain text and parse into trackables
     *
     * @param  $text Plain text content
     *
     * @return array
     */
    protected function extractTrackablesFromText($text)
    {
        // Plaintext links
        $trackableUrls = array();
        if (preg_match_all('/((https?|ftps?):\/\/)([a-zA-Z0-9-\.{}]*[a-zA-Z0-9=}]*)(\??)([^\s\]]+)?/i', $text, $matches)) {
            foreach ($matches[0] as $url) {
                if ($preparedUrl = $this->prepareUrlForTracking($url)) {
                    list($urlKey, $urlValue) = $preparedUrl;
                    $trackableUrls[$urlKey] = $urlValue;
                }
            }
        }

        return $trackableUrls;
    }

    /**
     * Create a Trackable entity
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
     * Validate and parse link for tracking
     *
     * @param $url
     *
     * @return array[$trackingKey, $trackingUrl]|false
     */
    protected function prepareUrlForTracking($url)
    {
        // Ensure it's clean
        $url = trim($url);

        // Default key and final URL to the given $url
        $trackableKey = $trackableUrl = $url;

        // Convert URL
        $urlParts = parse_url($url);

        // Ensure a valid scheme
        if (isset($urlParts['scheme']) && !in_array($urlParts['scheme'], array('http', 'https', 'ftp', 'ftps'))) {

            return false;
        }

        // Ensure a applicable URL (rule out URLs as just #)
        if (!isset($urlParts['host']) && !isset($urlParts['path'])) {

            return false;
        }

        // Extract any tokens that are part of the query
        $tokenizedParams = $this->extractTokensFromQuery($urlParts['query']);

        // Check if URL is trackable
        $tokenizedHost = (!isset($urlParts['host']) && isset($urlParts['path'])) ? $urlParts['path'] : $urlParts['host'];
        if (preg_match('/^({\S+?})/', $tokenizedHost, $match)) {
            $token = $match[1];

            // Validate that the token is something that can be trackable
            if (!$this->validateTokenIsTrackable($token, $tokenizedHost)) {

                return false;
            }

            // Tokenized hosts shouldn't use a scheme since the token value should contain it
            if ($scheme = (!empty($urlParts['scheme'])) ? $urlParts['scheme'] : false) {
                // Token has a schema so let's get rid of it before replacing tokens
                $this->contentReplacements['firstPass'][$scheme.'://'.$tokenizedHost] = $tokenizedHost;
                unset($urlParts['scheme']);
            }

            $trackableKey = $this->httpBuildUrl($urlParts);
            $trackableUrl = (!empty($urlParts['query'])) ? $this->contentTokens[$token].'?'.$urlParts['query'] : $this->contentTokens[$token];
        } else {
            // Regular URL without a tokenized host
            $trackableUrl = $this->httpBuildUrl($urlParts);

            if ($this->isInDoNotTrack($trackableUrl)) {

                return false;
            }
        }

        // Append tokenized params to the end of the URL as these will not be part of the stored redirect URL
        // They'll be passed through as regular parameters outside the trackable token
        // For example, {trackedlink=123}?foo={bar}
        if ($tokenizedParams) {
            $trackableUrl .= ((strpos($trackableUrl, '?') !== false) ? '&' : '?') .
                $this->httpBuildQuery($tokenizedParams);

            // Replace the original URL with the updated URL before replacing with tokens
            $this->contentReplacements['firstPass'][$url] = $trackableUrl;
        }

        return array($trackableKey, $trackableUrl);
    }

    /**
     * Determines if a URL/token is in the do not track list
     *
     * @param $url
     *
     * @return bool
     */
    protected function isInDoNotTrack($url)
    {
        // Ensure it's not in the do not track list
        foreach ($this->doNotTrack as $notTrackable) {
            if (preg_match('/'.$notTrackable.'/i', $url)) {

                return false;
            }
        }

        return true;
    }

    /**
     * Validates that a token is trackable as a URL
     *
     * @param      $token
     * @param null $tokenizedHost
     *
     * @return bool
     */
    protected function validateTokenIsTrackable($token, $tokenizedHost = null)
    {
        // Token as URL
        if ($tokenizedHost && !preg_match('/^({\S+?})$/', $tokenizedHost)) {
            // Currently this does not apply to something like "{leadfield=firstname}.com" since that could result in URL per lead

            return false;
        }

        // Validate if this token is listed as not to be tracked
        if ($this->isInDoNotTrack($token)) {

            return false;
        }

        // Validate that the token is
        if (!isset($this->contentTokens[$token])) {

            return false;
        }

        return true;
    }

    /**
     * Find and extract tokens from the URL as this have to be processed outside of tracking tokens
     *
     * @param $urlParts Array from parse_url
     *
     * @return array|false
     */
    protected function extractTokensFromQuery(&$urlParts)
    {
        $tokenizedParams = false;

        // Check for a token with a query appended such as {pagelink=1}&key=value
        if (isset($urlParts['path']) && preg_match('/([https?|ftps?]?\{.*?\})&amp;(.*?)$/', $urlParts['path'], $match)) {
            $urlParts['path'] = $match[1];
            if (isset($urlParts['query'])) {
                // Likely won't happen but append if this exists
                $urlParts['query'] .= '&amp;'.$match[2];
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
     * Group query parameters into those that have tokens and those that do not
     *
     * @param $query
     *
     * @return array[$tokenizedParams[], $untokenizedParams[]]
     */
    protected function parseTokenizedQuery($query)
    {
        $tokenizedParams =
        $untokenizedParams = array();

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

        return array($tokenizedParams, $untokenizedParams);
    }

    /**
     * @param $trackableUrls
     * @param $channel
     * @param $channelId
     *
     * @return array
     */
    private function getEntitiesFromUrls($trackableUrls, $channel, $channelId)
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
    private function httpBuildUrl($parts)
    {
        if (function_exists('http_build_url')) {

            return http_build_url($parts);
        } else {
            /**
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
            $url   = array();

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
                foreach (array('user', 'pass', 'port', 'path', 'query', 'fragment') as $key) {
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
     * Build query string while accounting for tokens that include an equal sign
     *
     * @param array $queryParts
     *
     * @return mixed|string
     */
    private function httpBuildQuery(array $queryParts)
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
