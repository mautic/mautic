<?php

namespace Mautic\CoreBundle\Helper;

class UrlHelper
{
    /**
     * Append query string to URL.
     *
     * @param string $url
     * @param string $appendQueryString
     *
     * @return string
     */
    public static function appendQueryToUrl($url, $appendQueryString)
    {
        $query     = parse_url($url, PHP_URL_QUERY);

        if ($query) {
            $appendQueryString = '&'.$appendQueryString;
        } else {
            $appendQueryString = '?'.$appendQueryString;
        }

        $anchorParts = explode('#', $url);
        // join url without anchor + $appendQueryString
        $url = $anchorParts[0].$appendQueryString;
        // prevent & or ? twice
        $url = str_replace(['&&', '??'], ['&', '?'], $url);

        // anchor
        if (isset($anchorParts[1])) {
            $url = sprintf('%s#%s', $url, $anchorParts[1]);
        }

        return $url;
    }

    /**
     * @return string
     */
    public static function rel2abs($rel)
    {
        $path = $host = $scheme = '';

        $ssl    = !empty($_SERVER['HTTPS']) && 'on' == $_SERVER['HTTPS'];
        $scheme = strtolower($_SERVER['SERVER_PROTOCOL']);
        $scheme = substr($scheme, 0, strpos($scheme, '/')).($ssl ? 's' : '');
        $port   = $_SERVER['SERVER_PORT'];
        $port   = ((!$ssl && '80' == $port) || ($ssl && '443' == $port)) ? '' : ":$port";
        $host   = $_SERVER['HTTP_HOST'] ?? null;
        $host ??= $_SERVER['SERVER_NAME'].$port;
        $base   = "$scheme://$host".$_SERVER['REQUEST_URI'];

        $base = str_replace('/index.php', '', $base);

        /* return if already absolute URL */
        if ('' != parse_url($rel, PHP_URL_SCHEME)) {
            return $rel;
        }

        /* queries and anchors */
        if ('#' == $rel[0] || '?' == $rel[0]) {
            return $base.$rel;
        }

        /* after parse base URL and convert to local variables:
           $scheme, $host, $path */
        $urlPartsArray = parse_url($base);

        // We should have a valid URL by this point. If not, just return the original value
        if (false === $urlPartsArray) {
            return $rel;
        }

        extract($urlPartsArray);

        /* remove non-directory element from path */
        $path = preg_replace('#/[^/]*$#', '', $path);

        /* destroy path if relative url points to root */
        if ('/' == $rel[0]) {
            $path = '';
        }

        /* dirty absolute URL // with port number if exists */
        if ('' != parse_url($base, PHP_URL_PORT)) {
            $abs = "$host:".parse_url($base, PHP_URL_PORT)."$path/$rel";
        } else {
            $abs = "$host$path/$rel";
        }
        /* replace '//' or '/./' or '/foo/../' with '/' */
        $re = ['#(/\.?/)#', '#/(?!\.\.)[^/]+/\.\./#'];
        for ($n = 1; $n > 0;) {
            $abs = preg_replace($re, '/', $abs, -1, $n);
        }

        /* absolute URL is ready! */
        return $scheme.'://'.$abs;
    }

    /**
     * Takes a plaintext, finds all URLs in it and return the array of those URLs.
     * With exception of URLs used as a token default values.
     *
     * @param string $text
     */
    public static function getUrlsFromPlaintext($text, array $contactUrlFields = []): array
    {
        $urls = [];
        // Check if there are any tokens that URL based fields
        foreach ($contactUrlFields as $field) {
            if (str_contains($text, "{contactfield=$field}")) {
                $urls[] = "{contactfield=$field}";
            }
        }

        $regex = '_(?:(?:https?|ftp)://)(?:\S+(?::\S*)?@)?(?:(?!10(?:\.\d{1,3}){3})(?!127(?:\.\d{1,3}){3})(?!169\.254(?:\.\d{1,3}){2})(?!192\.168(?:\.\d{1,3}){2})(?!172\.(?:1[6-9]|2\d|3[0-1])(?:\.\d{1,3}){2})(?:[1-9]\d?|1\d\d|2[01]\d|22[0-3])(?:\.(?:1?\d{1,2}|2[0-4]\d|25[0-5])){2}(?:\.(?:[1-9]\d?|1\d\d|2[0-4]\d|25[0-4]))|(?:(?:[a-z\x{00a1}-\x{ffff}0-9]+-?)*[a-z\x{00a1}-\x{ffff}0-9]+)(?:\.(?:[a-z\x{00a1}-\x{ffff}0-9]+-?)*[a-z\x{00a1}-\x{ffff}0-9]+)*(?:\.(?:[a-z\x{00a1}-\x{ffff}]{2,})))(?::\d{2,5})?(?:/[^\s]*)?_ius';
        if (!preg_match_all($regex, $text, $matches)) {
            return $urls;
        }

        $urls = array_merge($urls, $matches[0]);

        foreach ($urls as $key => $url) {
            // Remove dangling punctuation
            $urls[$key] = $url = self::removeTrailingNonAlphaNumeric($url);

            // We don't want to match URLs in token default values
            // like {contactfield=website|http://ignore.this.url}
            if (preg_match_all("/{(.*?)\|".preg_quote($url, '/').'}/', $text, $matches)) {
                unset($urls[$key]);

                // We know this is a URL due to the default so let's include it as a trackable
                foreach ($matches[1] as $tokenKey => $tokenContent) {
                    $urls[] = $matches[0][$tokenKey];
                }
            }
        }

        return $urls;
    }

    /**
     * Sanitize parts of the URL to make sure the URL query values are HTTP encoded.
     *
     * @param string $url
     *
     * @return string
     */
    public static function sanitizeAbsoluteUrl($url)
    {
        if (!$url) {
            return $url;
        }

        $url = self::sanitizeUrlScheme($url);
        $url = self::sanitizeUrlPath($url);

        return self::sanitizeUrlQuery($url);
    }

    /**
     * Make sure the URL has a scheme. Defaults to HTTP if not provided.
     *
     * @param string $url
     *
     * @return string
     */
    private static function sanitizeUrlScheme($url)
    {
        $isRelative = str_starts_with($url, '//');

        if ($isRelative) {
            return $url;
        }

        $isMailto = str_starts_with($url, 'mailto:');

        if ($isMailto) {
            return $url;
        }

        $containSlashes = str_contains($url, '://');

        if (!$containSlashes) {
            $url = sprintf('://%s', $url);
        }

        $scheme = parse_url($url, PHP_URL_SCHEME);

        // Set default scheme to http if missing
        if (empty($scheme)) {
            $url = sprintf('http%s', $url);
        }

        return $url;
    }

    /**
     * @param string $url
     *
     * @return string
     */
    private static function sanitizeUrlPath($url)
    {
        $path = parse_url($url, PHP_URL_PATH);

        if (!empty($path)) {
            $sanitizedPath = str_replace(' ', '%20', $path);
            $url           = str_replace($path, $sanitizedPath, $url);
        }

        return $url;
    }

    /**
     * @param string $url
     *
     * @return string
     */
    private static function sanitizeUrlQuery($url)
    {
        $query = parse_url($url, PHP_URL_QUERY);

        if (!empty($query)) {
            parse_str(str_replace('?', '&', $query), $parsedQuery);

            if ($parsedQuery) {
                $encodedQuery = http_build_query($parsedQuery);
                $url          = str_replace('?'.$query, '?'.$encodedQuery, $url);
            }
        }

        return $url;
    }

    /**
     * @param string $string
     *
     * @return string
     */
    private static function removeTrailingNonAlphaNumeric($string)
    {
        // Special handling of closing bracket
        if (str_ends_with($string, '}') && preg_match('/^[^{\r\n]*\}.*?$/', $string)) {
            $string = substr($string, 0, -1);

            return self::removeTrailingNonAlphaNumeric($string);
        }

        // Ensure only alphanumeric allowed
        if (!preg_match("/^.*?[a-zA-Z0-9}\/]$/i", $string)) {
            $string = substr($string, 0, -1);

            return self::removeTrailingNonAlphaNumeric($string);
        }

        return $string;
    }

    /**
     *  This method return true with special characters in URL, for example https://domain.tld/é.pdf
     * filter_var($url, FILTER_VALIDATE_URL) allow only alphanumerics [0-9a-zA-Z], the special characters "$-_.+!*'()," [not including the quotes - ed].
     *
     * @param string $url
     */
    public static function isValidUrl($url): bool
    {
        $path         = parse_url($url, PHP_URL_PATH);
        $encodedPath  = array_map('urlencode', explode('/', $path));
        $url          = str_replace($path, implode('/', $encodedPath), $url);

        return (bool) filter_var($url, FILTER_VALIDATE_URL);
    }

    /**
     * Decode &amp; (HTML), &#38; (decimal) and &#x26; (hex) ampersands.
     * This even works with double encoded ampersands.
     *
     * @param string $url
     *
     * @return string
     */
    public static function decodeAmpersands($url)
    {
        while (str_contains($url, '&amp;') || str_contains($url, '&#38;') || str_contains($url, '&#x26;')) {
            $url = str_replace(['&amp;', '&#38;', '&#x26;'], '&', $url);
        }

        return $url;
    }

    /**
     * This method implements unicode slugs instead of transliteration.
     */
    public static function stringURLUnicodeSlug(string $string): string
    {
        // Replace double byte whitespaces by single byte (East Asian languages)
        $str = preg_replace('/\xE3\x80\x80/', ' ', $string);

        // Remove any '-' from the string as they will be used as concatenator.
        // Would be great to let the spaces in but only Firefox is friendly with this
        $str = str_replace('-', ' ', $str);

        // Replace forbidden characters by whitespaces
        $str = preg_replace('#[:\#\*"@+=;!><&\.%()\]\/\'\\\\|\[]#', "\x20", $str);

        // Delete all '?'
        $str = str_replace('?', '', $str);

        // Trim white spaces at beginning and end of alias and make lowercase
        $str = trim(strtolower($str));

        // Remove any duplicate whitespace and replace whitespaces by hyphens
        return preg_replace('#\x20+#', '-', $str);
    }
}
