<?php

/*
 * @copyright   2015 Mautic Contributors. All rights reserved
 * @copyright   2005-2007 Jon Abernathy <jon@chuggnutt.com>
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Helper;

class PlainTextHelper
{
    const ENCODING = 'UTF-8';

    /**
     * Contains the HTML content to convert.
     *
     * @var string
     */
    protected $html;

    /**
     * Contains the converted, formatted text.
     *
     * @var string
     */
    protected $text;

    /**
     * Maximum width of the formatted text, in columns.
     *
     * Set this value to 0 (or less) to ignore word wrapping
     * and not constrain text to a fixed-width column.
     *
     * @var int
     */
    protected $width = 70;

    /**
     * List of preg* regular expression patterns to search for,
     * used in conjunction with $replace.
     *
     * @var array
     *
     * @see $replace
     */
    protected $search = [
        "/\r/",                                           // Non-legal carriage return
        "/[\n\t]+/",                                      // Newlines and tabs
        '/<head[^>]*>.*?<\/head>/i',                      // <head>
        '/<script[^>]*>.*?<\/script>/i',                  // <script>s -- which strip_tags supposedly has problems with
        '/<style[^>]*>.*?<\/style>/i',                    // <style>s -- which strip_tags supposedly has problems with
        '/<p[^>]*>/i',                                    // <P>
        '/<br[^>]*>/i',                                   // <br>
        '/<i[^>]*>(.*?)<\/i>/i',                          // <i>
        '/<em[^>]*>(.*?)<\/em>/i',                        // <em>
        '/(<ul[^>]*>|<\/ul>)/i',                          // <ul> and </ul>
        '/(<ol[^>]*>|<\/ol>)/i',                          // <ol> and </ol>
        '/(<dl[^>]*>|<\/dl>)/i',                          // <dl> and </dl>
        '/<li[^>]*>(.*?)<\/li>/i',                        // <li> and </li>
        '/<dd[^>]*>(.*?)<\/dd>/i',                        // <dd> and </dd>
        '/<dt[^>]*>(.*?)<\/dt>/i',                        // <dt> and </dt>
        '/<li[^>]*>/i',                                   // <li>
        '/<hr[^>]*>/i',                                   // <hr>
        '/<div[^>]*>/i',                                  // <div>
        '/(<table[^>]*>|<\/table>)/i',                    // <table> and </table>
        '/(<tr[^>]*>|<\/tr>)/i',                          // <tr> and </tr>
        '/<td[^>]*>(.*?)<\/td>/i',                        // <td> and </td>
        '/<span class="_html2text_ignore">.+?<\/span>/i', // <span class="_html2text_ignore">...</span>
    ];

    /**
     * List of pattern replacements corresponding to patterns searched.
     *
     * @var array
     *
     * @see $search
     */
    protected $replace = [
        '',                              // Non-legal carriage return
        ' ',                             // Newlines and tabs
        '',                              // <head>
        '',                              // <script>s -- which strip_tags supposedly has problems with
        '',                              // <style>s -- which strip_tags supposedly has problems with
        "\n\n",                          // <P>
        "\n",                            // <br>
        '_\\1_',                         // <i>
        '_\\1_',                         // <em>
        "\n\n",                          // <ul> and </ul>
        "\n\n",                          // <ol> and </ol>
        "\n\n",                          // <dl> and </dl>
        "\t* \\1\n",                     // <li> and </li>
        " \\1\n",                        // <dd> and </dd>
        "\t* \\1",                       // <dt> and </dt>
        "\n\t* ",                        // <li>
        "\n-------------------------\n", // <hr>
        "<div>\n",                       // <div>
        "\n\n",                          // <table> and </table>
        "\n",                            // <tr> and </tr>
        "\t\t\\1\n",                     // <td> and </td>
        '',                               // <span class="_html2text_ignore">...</span>
    ];

    /**
     * List of preg* regular expression patterns to search for,
     * used in conjunction with $entReplace.
     *
     * @var array
     *
     * @see $entReplace
     */
    protected $entSearch = [
        '/&#153;/i',                                     // TM symbol in win-1252
        '/&#151;/i',                                     // m-dash in win-1252
        '/&(amp|#38);/i',                                // Ampersand: see converter()
        '/[ ]{2,}/',                                     // Runs of spaces, post-handling
    ];

    /**
     * List of pattern replacements corresponding to patterns searched.
     *
     * @var array
     *
     * @see $entSearch
     */
    protected $entReplace = [
        '™',         // TM symbol
        '—',         // m-dash
        '|+|amp|+|', // Ampersand: see converter()
        ' ',         // Runs of spaces, post-handling
    ];

    /**
     * List of preg* regular expression patterns to search for
     * and replace using callback function.
     *
     * @var array
     */
    protected $callbackSearch = [
        '/<(h)[123456]( [^>]*)?>(.*?)<\/h[123456]>/i',           // h1 - h6
        '/<(b)( [^>]*)?>(.*?)<\/b>/i',                           // <b>
        '/<(strong)( [^>]*)?>(.*?)<\/strong>/i',                 // <strong>
        '/<(th)( [^>]*)?>(.*?)<\/th>/i',                         // <th> and </th>
        '/<(a) [^>]*href=("|\')([^"\']+)\2([^>]*)>(.*?)<\/a>/i',  // <a href="">
    ];

    /**
     * List of preg* regular expression patterns to search for in PRE body,
     * used in conjunction with $preReplace.
     *
     * @var array
     *
     * @see $preReplace
     */
    protected $preSearch = [
        "/\n/",
        "/\t/",
        '/ /',
        '/<pre[^>]*>/',
        '/<\/pre>/',
    ];

    /**
     * List of pattern replacements corresponding to patterns searched for PRE body.
     *
     * @var array
     *
     * @see $preSearch
     */
    protected $preReplace = [
        '<br>',
        '&nbsp;&nbsp;&nbsp;&nbsp;',
        '&nbsp;',
        '',
        '',
    ];

    /**
     * Temporary workspace used during PRE processing.
     *
     * @var string
     */
    protected $preContent = '';

    /**
     * Indicates whether content in the $html variable has been converted yet.
     *
     * @var bool
     *
     * @see $html, $text
     */
    protected $converted = false;

    /**
     * Contains URL addresses from links to be rendered in plain text.
     *
     * @var array
     *
     * @see buildlinkList()
     */
    protected $linkList = [];

    /**
     * Various configuration options (able to be set in the constructor).
     *
     * @var array
     */
    protected $options = [
        'do_links' => 'inline', // 'none'
        // 'inline' (show links inline)
        // 'nextline' (show links on the next line)
        // 'table' (if a table of link URLs should be listed after the text.

        'width' => 70,          //  Maximum width of the formatted text, in columns.
        //  Set this value to 0 (or less) to ignore word wrapping
        //  and not constrain text to a fixed-width column.

        'base_url' => '',
    ];

    /**
     * @param string $html    Source HTML
     * @param array  $options Set configuration options
     */
    public function __construct($html = '', $options = [])
    {
        if (is_array($html)) {
            // Options were passed in without html
            $options = $html;
            $html    = '';
        }

        $this->html    = $html;
        $this->options = array_merge($this->options, $options);
    }

    /**
     * Set the source HTML.
     *
     * @param string $html HTML source content
     *
     * @return PlainTextHelper
     */
    public function setHtml($html)
    {
        $this->html      = $html;
        $this->converted = false;

        return $this;
    }

    /**
     * Returns the text, converted from HTML.
     *
     * @return string
     */
    public function getText()
    {
        if (!$this->converted) {
            $this->convert();
        }

        return trim($this->text);
    }

    protected function convert()
    {
        $this->linkList = [];

        $text = trim(stripslashes($this->html));

        $this->converter($text);

        if ($this->linkList) {
            $text .= "\n\nLinks:\n------\n";
            foreach ($this->linkList as $i => $url) {
                $text .= '['.($i + 1).'] '.$url."\n";
            }
        }

        $this->text = $text;

        $this->converted = true;
    }

    protected function converter(&$text)
    {
        $this->convertBlockquotes($text);
        $this->convertPre($text);
        $text = preg_replace($this->search, $this->replace, $text);
        $text = preg_replace_callback($this->callbackSearch, [$this, 'pregCallback'], $text);
        $text = strip_tags($text);
        $text = preg_replace($this->entSearch, $this->entReplace, $text);
        $text = html_entity_decode($text, ENT_QUOTES, self::ENCODING);

        // Remove unknown/unhandled entities (this cannot be done in search-and-replace block)
        $text = preg_replace('/&([a-zA-Z0-9]{2,6}|#[0-9]{2,4});/', '', $text);

        // Convert "|+|amp|+|" into "&", need to be done after handling of unknown entities
        // This properly handles situation of "&amp;quot;" in input string
        $text = str_replace('|+|amp|+|', '&', $text);

        // Normalise empty lines
        $text = preg_replace("/\n\s+\n/", "\n\n", $text);
        $text = preg_replace("/[\n]{3,}/", "\n\n", $text);

        // remove leading empty lines (can be produced by eg. P tag on the beginning)
        $text = ltrim($text, "\n");

        if ($this->options['width'] > 0) {
            $text = $this->linewrap($text, $this->options['width']);
        }
    }

    /**
     * Helper function called by preg_replace() on link replacement.
     *
     * Maintains an internal list of links to be displayed at the end of the
     * text, with numeric indices to the original point in the text they
     * appeared. Also makes an effort at identifying and handling absolute
     * and relative links.
     *
     * @param string $link         URL of the link
     * @param string $display      Part of the text to associate number with
     * @param null   $linkOverride
     *
     * @return string
     */
    protected function buildlinkList($link, $display, $linkOverride = null)
    {
        $linkMethod = ($linkOverride) ? $linkOverride : $this->options['do_links'];
        if ($linkMethod == 'none') {
            return $display;
        }

        // Ignored link types
        if (preg_match('!^(javascript:|mailto:|#)!i', $link)) {
            return $display;
        }

        if (preg_match('!^([a-z][a-z0-9.+-]+:)!i', $link) || preg_match('!({|%7B)(.*?)(}|%7D)!', $link)) {
            $url = $link;
        } else {
            $url = $this->options['base_url'];
            if (substr($link, 0, 1) != '/') {
                $url .= '/';
            }
            $url .= $link;
        }

        if ($linkMethod == 'table') {
            if (($index = array_search($url, $this->linkList)) === false) {
                $index            = count($this->linkList);
                $this->linkList[] = $url;
            }

            return $display.' ['.($index + 1).']';
        } elseif ($linkMethod == 'nextline') {
            return $display."\n[".$url.']';
        } else { // link_method defaults to inline
            return $display.' ['.$url.']';
        }
    }

    protected function convertPre(&$text)
    {
        // get the content of PRE element
        while (preg_match('/<pre[^>]*>(.*)<\/pre>/ismU', $text, $matches)) {
            $this->preContent = $matches[1];

            // Run our defined tags search-and-replace with callback
            $this->preContent = preg_replace_callback(
                $this->callbackSearch,
                [$this, 'pregCallback'],
                $this->preContent
            );

            // convert the content
            $this->preContent = sprintf(
                '<div><br>%s<br></div>',
                preg_replace($this->preSearch, $this->preReplace, $this->preContent)
            );

            // replace the content (use callback because content can contain $0 variable)
            $text = preg_replace_callback(
                '/<pre[^>]*>.*<\/pre>/ismU',
                [$this, 'pregPreCallback'],
                $text,
                1
            );

            // free memory
            $this->preContent = '';
        }
    }

    /**
     * Helper function for BLOCKQUOTE body conversion.
     *
     * @param string $text HTML content
     */
    protected function convertBlockquotes(&$text)
    {
        if (preg_match_all('/<\/*blockquote[^>]*>/i', $text, $matches, PREG_OFFSET_CAPTURE)) {
            $start  = 0;
            $taglen = 0;
            $level  = 0;
            $diff   = 0;
            foreach ($matches[0] as $m) {
                if ($m[0][0] == '<' && $m[0][1] == '/') {
                    --$level;
                    if ($level < 0) {
                        $level = 0; // malformed HTML: go to next blockquote
                    } elseif ($level > 0) {
                        // skip inner blockquote
                    } else {
                        $end = $m[1];
                        $len = $end - $taglen - $start;
                        // Get blockquote content
                        $body = substr($text, $start + $taglen - $diff, $len);

                        // Set text width
                        $pWidth = $this->options['width'];
                        if ($this->options['width'] > 0) {
                            $this->options['width'] -= 2;
                        }
                        // Convert blockquote content
                        $body = trim($body);
                        $this->converter($body);
                        // Add citation markers and create PRE block
                        $body = preg_replace('/((^|\n)>*)/', '\\1> ', trim($body));
                        $body = '<pre>'.htmlspecialchars($body).'</pre>';
                        // Re-set text width
                        $this->options['width'] = $pWidth;
                        // Replace content
                        $text = substr($text, 0, $start - $diff)
                            .$body.substr($text, $end + strlen($m[0]) - $diff);

                        $diff = $len + $taglen + strlen($m[0]) - strlen($body);
                        unset($body);
                    }
                } else {
                    if ($level == 0) {
                        $start  = $m[1];
                        $taglen = strlen($m[0]);
                    }
                    ++$level;
                }
            }
        }
    }

    /**
     * Callback function for preg_replace_callback use.
     *
     * @param array $matches PREG matches
     *
     * @return string
     */
    protected function pregCallback($matches)
    {
        switch (strtolower($matches[1])) {
            case 'b':
            case 'strong':
                return $matches[3];
            case 'th':
                return $this->toupper("\t\t".$matches[3]."\n");
            case 'h':
                return $this->toupper("\n\n".$matches[3]."\n\n");
            case 'a':
                // override the link method
                $linkOverride = null;
                if (preg_match('/_html2text_link_(\w+)/', $matches[4], $linkOverrideMatch)) {
                    $linkOverride = $linkOverrideMatch[1];
                }
                // Remove spaces in URL (#1487805)
                $url = str_replace(' ', '', $matches[3]);

                return $this->buildlinkList($url, $matches[5], $linkOverride);
        }

        return '';
    }

    /**
     * Callback function for preg_replace_callback use in PRE content handler.
     *
     * @param array $matches PREG matches
     *
     * @return string
     */
    protected function pregPreCallback(/* @noinspection PhpUnusedParameterInspection */ $matches)
    {
        return $this->preContent;
    }

    /**
     * Strtoupper function with HTML tags and entities handling.
     *
     * @param string $str Text to convert
     *
     * @return string Converted text
     */
    private function toupper($str)
    {
        // string can contain HTML tags
        $chunks = preg_split('/(<[^>]*>)/', $str, null, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);

        // convert toupper only the text between HTML tags
        foreach ($chunks as $i => $chunk) {
            if ($chunk[0] != '<') {
                $chunks[$i] = $this->strtoupper($chunk);
            }
        }

        return implode($chunks);
    }

    /**
     * Strtoupper multibyte wrapper function with HTML entities handling.
     *
     * @param string $str Text to convert
     *
     * @return string Converted text
     */
    private function strtoupper($str)
    {
        $str = html_entity_decode($str, ENT_COMPAT, self::ENCODING);

        if (function_exists('mb_strtoupper')) {
            $str = mb_strtoupper($str, self::ENCODING);
        } else {
            $str = strtoupper($str);
        }

        $str = htmlspecialchars($str, ENT_COMPAT, self::ENCODING);

        return $str;
    }

    /**
     * @param            $text
     * @param            $width
     * @param string     $breakline
     * @param bool|false $cut
     *
     * @return string
     */
    private function linewrap($text, $width, $breakline = "\n", $cut = false)
    {
        $lines = explode("\n", $text);
        $text  = '';
        foreach ($lines as $line) {
            $text .= trim(wordwrap(trim($line), $width, $breakline, $cut));
            $text .= "\n";
        }

        return $text;
    }
}
