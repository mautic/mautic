<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PageBundle\EventListener;

use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\CoreBundle\Helper\BuilderTokenHelper;
use Mautic\PageBundle\Event as Events;
use Mautic\PageBundle\PageEvents;
use Mautic\EmailBundle\EmailEvents;
use Mautic\EmailBundle\Event\EmailBuilderEvent;
use Mautic\EmailBundle\Event\EmailSendEvent;

/**
 * Class BuilderSubscriber
 */
class BuilderSubscriber extends CommonSubscriber
{
    private $pageTokenRegex = '{pagelink=(.*?)}';
    private $externalTokenRegex = '{externallink=(.*?)}';
    private $langBarRegex = '{langbar}';
    private $shareButtonsRegex = '{sharebuttons}';
    private $emailIsInternalSend = false;
    private $emailEntity = null;
    private $emailTrackedLinkSettings = array();

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return array(
            PageEvents::PAGE_ON_DISPLAY   => array('onPageDisplay', 0),
            PageEvents::PAGE_ON_BUILD     => array('onPageBuild', 0),
            EmailEvents::EMAIL_ON_BUILD   => array('onEmailBuild', 0),
            // Make sure these are last priority in order to catch all links
            EmailEvents::EMAIL_ON_SEND    => array('onEmailGenerate', -254),
            EmailEvents::EMAIL_ON_DISPLAY => array('onEmailGenerate', -254)
        );
    }

    /**
     * Add forms to available page tokens
     *
     * @param Events\PageBuilderEvent $event
     */
    public function onPageBuild(Events\PageBuilderEvent $event)
    {
        $tokenHelper = new BuilderTokenHelper($this->factory, 'page');

        if ($event->tokenSectionsRequested()) {
            //add extra tokens
            $content = $this->templating->render('MauticPageBundle:SubscribedEvents\PageToken:token.html.php');
            $event->addTokenSection('page.extratokens', 'mautic.page.builder.header.extra', $content, 2);

            //add pagetokens
            $event->addTokenSection(
                'page.pagetokens',
                'mautic.page.pages',
                $tokenHelper->getTokenContent(
                    array(
                        'filter' => array(
                            'force' => array(
                                array('column' => 'p.variantParent', 'expr' => 'isNull')
                            )
                        )
                    )
                ),
                -254
            );
        }

        if ($event->abTestWinnerCriteriaRequested()) {
            //add AB Test Winner Criteria
            $bounceRate = array(
                'group'    => 'mautic.page.abtest.criteria',
                'label'    => 'mautic.page.abtest.criteria.bounce',
                'callback' => '\Mautic\PageBundle\Helper\AbTestHelper::determineBounceTestWinner'
            );
            $event->addAbTestWinnerCriteria('page.bouncerate', $bounceRate);

            $dwellTime = array(
                'group'    => 'mautic.page.abtest.criteria',
                'label'    => 'mautic.page.abtest.criteria.dwelltime',
                'callback' => '\Mautic\PageBundle\Helper\AbTestHelper::determineDwellTimeTestWinner'
            );
            $event->addAbTestWinnerCriteria('page.dwelltime', $dwellTime);
        }

        if ($event->tokensRequested(array($this->pageTokenRegex))) {
            $event->addTokensFromHelper($tokenHelper, $this->pageTokenRegex, 'title', 'id', false, true);

            $event->addTokens(
                $event->filterTokens(
                    array(
                        $this->shareButtonsRegex => $this->translator->trans('mautic.page.token.lang'),
                        $this->langBarRegex      => $this->translator->trans('mautic.page.token.share'),
                    )
                )
            );
        }
    }

    /**
     * @param Events\PageDisplayEvent $event
     */
    public function onPageDisplay(Events\PageDisplayEvent $event)
    {
        $content = $event->getContent();
        $page    = $event->getPage();

        if (strpos($content, $this->langBarRegex) !== false) {
            $langbar = $this->renderLanguageBar($page);
            $content = str_ireplace($this->langBarRegex, $langbar, $content);
        }

        if (strpos($content, $this->shareButtonsRegex) !== false) {
            $buttons = $this->renderSocialShareButtons();
            $content = str_ireplace($this->shareButtonsRegex, $buttons, $content);
        }

        $clickThrough   = array('source' => array('page', $page->getId()));
        $pageTokens     = $this->generatePageTokens($content, $clickThrough);
        $externalTokens = $this->generateExternalLinkTokens($content, $clickThrough);
        $tokens         = array_merge($pageTokens, $externalTokens);
        if (count($tokens)) {
            $content = str_ireplace(array_keys($tokens), $tokens, $content);
        }

        $event->setContent($content);
    }

    /**
     * Renders the HTML for the social share buttons
     *
     * @return string
     */
    protected function renderSocialShareButtons()
    {
        static $content = "";

        if (empty($content)) {
            $shareButtons = $this->factory->getHelper('integration')->getShareButtons();

            $content = "<div class='share-buttons'>\n";
            foreach ($shareButtons as $network => $button) {
                $content .= $button;
            }
            $content .= "</div>\n";

            //load the css into the header by calling the sharebtn_css view
            $this->factory->getTemplating()->render('MauticPageBundle:SubscribedEvents\PageToken:sharebtn_css.html.php');
        }

        return $content;
    }

    /**
     * Renders the HTML for the language bar for a given page
     *
     * @param $page
     *
     * @return string
     */
    protected function renderLanguageBar($page)
    {
        static $langbar = '';

        if (empty($langbar)) {
            $model    = $this->factory->getModel('page.page');
            $parent   = $page->getTranslationParent();
            $children = $page->getTranslationChildren();

            //check to see if this page is grouped with another
            if (empty($parent) && empty($children)) {
                return;
            }

            $related = array();

            //get a list of associated pages/languages
            if (!empty($parent)) {
                $children = $parent->getTranslationChildren();
            } else {
                $parent = $page; //parent is self
            }

            if (!empty($children)) {
                $lang  = $parent->getLanguage();
                $trans = $this->translator->trans('mautic.page.lang.'.$lang);
                if ($trans == 'mautic.page.lang.'.$lang) {
                    $trans = $lang;
                }
                $related[$parent->getId()] = array(
                    "lang" => $trans,
                    "url"  => $model->generateUrl($parent, false)
                );
                foreach ($children as $c) {
                    $lang  = $c->getLanguage();
                    $trans = $this->translator->trans('mautic.page.lang.'.$lang);
                    if ($trans == 'mautic.page.lang.'.$lang) {
                        $trans = $lang;
                    }
                    $related[$c->getId()] = array(
                        "lang" => $trans,
                        "url"  => $model->generateUrl($c, false)
                    );
                }
            }

            //sort by language
            uasort(
                $related,
                function ($a, $b) {
                    return strnatcasecmp($a['lang'], $b['lang']);
                }
            );

            if (empty($related)) {
                return;
            }

            $langbar = $this->templating->render('MauticPageBundle:SubscribedEvents\PageToken:langbar.html.php', array('pages' => $related));
        }

        return $langbar;
    }

    /**
     * @param EmailBuilderEvent $event
     *
     * @return void
     */
    public function onEmailBuild(EmailBuilderEvent $event)
    {
        $tokenHelper = new BuilderTokenHelper($this->factory, 'page');

        if ($event->tokenSectionsRequested()) {
            $event->addTokenSection(
                'page.emailtokens',
                'mautic.page.pages',
                $tokenHelper->getTokenContent(
                    array(
                        'filter' => array(
                            'force' => array(
                                array('column' => 'p.variantParent', 'expr' => 'isNull')
                            )
                        )
                    )
                ),
                -254
            );
        }

        if ($event->tokensRequested(array($this->pageTokenRegex))) {
            $event->addTokensFromHelper($tokenHelper, $this->pageTokenRegex, 'title', 'id', false, true);
        }
    }

    /**
     * @param EmailSendEvent $event
     *
     * @return void
     */
    public function onEmailGenerate(EmailSendEvent $event)
    {
        $content      = $event->getContent();
        $plainText    = $event->getPlainText();
        $source       = $event->getSource();
        $email        = $event->getEmail();
        $clickthrough = array(
            //what entity is sending the email?
            'source' => $source,
            //the email being sent to be logged in page hit if applicable
            'email'  => ($email != null) ? $email->getId() : null,
            'stat'   => $event->getIdHash()
        );
        $lead         = $event->getLead();
        if ($lead !== null) {
            $clickthrough['lead'] = $lead['id'];
        }

        $this->emailIsInternalSend = $event->isInternalSend();
        $this->emailEntity         = $event->getEmail();

        // Generate page tokens first so they are available to convert to trackables
        $tokens = array_merge(
            $this->generatePageTokens($content.$plainText, (($event->shouldAppendClickthrough()) ? $clickthrough : array())),
            $this->generateExternalLinkTokens($content.$plainText, $clickthrough)
        );
        $event->addTokens($tokens);

        // Convert links to trackables if there is an email entity
        if (!$event->isInternalSend() && null !== $email) {
            $event->addTokens(
                $this->generateEmailTokens($clickthrough, $event)
            );
        }
    }

    /**
     * @param $content
     * @param $clickthrough
     *
     * @return array
     */
    protected function generatePageTokens($content, $clickthrough = array())
    {
        /** @var \Mautic\PageBundle\Model\PageModel $pageModel */
        $pageModel = $this->factory->getModel('page');

        preg_match_all('/'.$this->pageTokenRegex.'/', $content, $matches);

        $tokens = array();
        if (!empty($matches[1])) {
            foreach ($matches[1] as $key => $pageId) {
                $token = $matches[0][$key];
                if (!empty($tokens[$token])) {

                    continue;
                }

                $page = $pageModel->getEntity($pageId);

                if (!$page) {

                    continue;
                }

                $tokens[$token] = $pageModel->generateUrl($page, true, $clickthrough);
            }

            unset($matches);
        }

        return $tokens;
    }

    /**
     * @param                $clickthrough
     * @param EmailSendEvent $event
     *
     * @return array
     */
    protected function generateEmailTokens($clickthrough, EmailSendEvent $event)
    {
        static $emailTrackedLinks = array();

        /** @var \Mautic\PageBundle\Model\RedirectModel $redirectModel */
        $redirectModel = $this->factory->getModel('page.redirect');

        $emailId = $this->emailEntity->getId();

        if (!isset($emailTrackedLinks[$emailId])) {
            // Built by multiple functions
            $this->emailTrackedLinkSettings = array(
                'trackedLinks'         => array(),
                'firstContentReplace'  => array(),
                'secondContentReplace' => array(),
                'usingClickthrough'    => (!empty($clickthrough))
            );

            $this->convertTrackableLinks($event);

            if (!empty($this->emailTrackedLinkSettings['trackedLinks'])) {
                foreach ($this->emailTrackedLinkSettings['trackedLinks'] as $url => $link) {
                    $this->emailTrackedLinkSettings['secondContentReplace'][$url] = '{trackedlink='.$link->getRedirectId().'}';
                }

                // Sort longer to shorter strings to ensure that URLs that share the same base are appropriately replaced
                krsort($this->emailTrackedLinkSettings['secondContentReplace']);
            }

            $emailTrackedLinks[$emailId] = $this->emailTrackedLinkSettings;
            unset($this->emailTrackedLinkSettings);
        }

        $tokens = array();

        // Generate trackable URLs with lead specific click through
        foreach ($emailTrackedLinks[$emailId]['trackedLinks'] as $url => $link) {
            $trackedUrl                                         = $redirectModel->generateRedirectUrl($link, $clickthrough);
            $tokens['{trackedlink='.$link->getRedirectId().'}'] = $trackedUrl;
        }

        $leadId = !empty($clickthrough) ? $clickthrough['lead'] : '0';

        // Search/replace
        if (!isset($emailTrackedLinks[$emailId]['contentReplaced'][$leadId])) {
            // Sort longer to shorter strings to ensure that URLs that share the same base are appropriately replaced
            krsort($emailTrackedLinks[$emailId]['secondContentReplace']);

            $firstSearch   = array_keys($emailTrackedLinks[$emailId]['firstContentReplace']);
            $firstReplace  = $emailTrackedLinks[$emailId]['firstContentReplace'];
            $secondSearch  = array_keys($emailTrackedLinks[$emailId]['secondContentReplace']);
            $secondReplace = $emailTrackedLinks[$emailId]['secondContentReplace'];

            // Remove tracking tags from content
            $firstSearch[]  = 'mautic:disable-tracking=""'; // Editor may convert to HTML4
            $firstSearch[]  = 'mautic:disable-tracking'; // HTML5
            $firstReplace[] = '';
            $firstReplace[] = '';

            // For plain text, just do a search/replace
            if ($plainText = $event->getPlainText()) {
                $plainText = str_ireplace($firstSearch, $firstReplace, $plainText);
                $plainText = str_ireplace($secondSearch, $secondReplace, $plainText);
                $event->setPlainText($plainText);
            }

            // Main content
            $content = $event->getContent();
            // Regular search/replace to prepare for the second pass
            $content = str_ireplace($firstSearch, $firstReplace, $content);
            // For HTML, replace only the links; leaving the link text (if a URL) intact
            foreach ($emailTrackedLinks[$emailId]['secondContentReplace'] as $search => $replace) {
                $content = preg_replace(
                    '/<a(.*?)href=(["\'])'.preg_quote($search, '/').'(.*?)\\2(.*?)>/i',
                    '<a$1href=$2'.$replace.'$3$2$4>',
                    $content
                );
            }
            $event->setContent($content);

            $emailTrackedLinks[$emailId]['contentReplaced'][$leadId] = true;

            unset($firstSearch, $firstReplace, $secondSearch, $secondSearch, $content, $plainText);
        }

        return $tokens;
    }

    /**
     * @deprecated Since version 1.1; to be removed in 2.0
     *
     * @param $content
     * @param $clickthrough
     *
     * @return array
     */
    protected function generateExternalLinkTokens($content, $clickthrough = array())
    {
        /** @var \Mautic\PageBundle\Model\RedirectModel $redirectModel */
        $redirectModel = $this->factory->getModel('page.redirect');

        $tokens = array();

        preg_match_all('/'.$this->externalTokenRegex.'/', $content, $matches);

        if (!empty($matches[1])) {
            $foundTokens = array();
            foreach ($matches[1] as $key => $match) {
                $token = $matches[0][$key];
                if (!empty($tokens[$token])) {
                    continue;
                }

                $foundTokens[$token] = $match;
            }

            if ($this->emailIsInternalSend) {
                // Just replace tokens with its own URL
                $tokens = array_merge($tokens, $foundTokens);
            } else {
                $links = $redirectModel->getRedirectListByUrls($foundTokens, $this->emailEntity);
                foreach ($links as $token => $link) {
                    $tokens[$token] = $redirectModel->generateRedirectUrl($link, $clickthrough);;

                    if (!$link->getId() && !isset($persistEntities[$token])) {
                        $persistEntities[$token] = $link;
                    }
                }

                if (!empty($persistEntities)) {
                    $redirectModel->saveEntities($persistEntities);
                }
            }

            unset($foundTokens, $links, $persistEntities);
        }

        return $tokens;
    }

    /**
     * Converts links to trackable links and tokens
     *
     * @param EmailSendEvent $event
     *
     * @return array
     */
    protected function convertTrackableLinks(EmailSendEvent $event)
    {
        $taggedDoNotTrack = array();

        // Get a list of tokens for the tokenized link conversion
        $currentTokens = $event->getTokens();

        // Parse the content for links
        $body = $event->getContent();

        // Find links using DOM to only find <a> tags
        $libxmlPreviousState = libxml_use_internal_errors(true);
        libxml_use_internal_errors(true);
        $dom = new \DOMDocument;
        $dom->loadHTML('<?xml encoding="UTF-8">'.$body);
        libxml_clear_errors();
        libxml_use_internal_errors($libxmlPreviousState);

        $links = $dom->getElementsByTagName('a');

        $foundLinks =
        $tokenizedLinks = array();

        /** @var \DOMElement $link */
        foreach ($links as $link) {
            $url = $link->getAttribute('href');

            // The editor will have converted & to &amp; but DOMDocument will have converted them back so this must be accounted for
            $url = str_replace('&', '&amp;', $url);

            // Check for a do not track
            if ($link->hasAttribute('mautic:disable-tracking')) {
                $taggedDoNotTrack[$url] = true;

                continue;
            }

            $this->validateLink($url, $currentTokens, $foundLinks);
        }

        // Process plain text as well
        $plainText = $event->getPlainText();

        if (!empty($plainText)) {
            // Plaintext links
            preg_match_all(
                '/((https?|ftps?):\/\/)([a-zA-Z0-9-\.{}]*[a-zA-Z0-9=}]*)(\??)([^\s\]]+)?/i',
                $plainText,
                $matches
            );

            if (!empty($matches[0])) {
                foreach ($matches[0] as $url) {
                    $url = trim($url);
                    // Validate the link if it has not already been marked as do not track by attribute in the HTML version
                    if (!isset($taggedDoNotTrack[$url])) {
                        $this->validateLink($url, $currentTokens, $foundLinks);
                    }
                }
            }
        }

        /** @var \Mautic\PageBundle\Model\RedirectModel $redirectModel */
        $redirectModel = $this->factory->getModel('page.redirect');

        if (!empty($foundLinks)) {
            $links = $redirectModel->getRedirectListByUrls($foundLinks, $this->emailEntity);

            foreach ($links as $url => $link) {
                if (!$link->getId() && !isset($persistEntities[$url])) {
                    $persistEntities[$url] = $link;
                }

                $this->emailTrackedLinkSettings['trackedLinks'][$url] = $link;
            }
        }

        if (!empty($persistEntities)) {
            // Save redirect entities
            $redirectModel->getRepository()->saveEntities($persistEntities);
        }

        unset($foundLinks, $links, $persistEntities);
    }

    /**
     * Validate link and parse query with tokens
     *
     * @param string $url
     * @param array  $currentTokens Tokens found in the email
     * @param array  $foundLinks    Array of links to convert to trackables
     */
    private function validateLink($url, $currentTokens, &$foundLinks)
    {
        static $doNotTrack;

        if (null === $doNotTrack) {
            $event      = $this->dispatcher->dispatch(
                PageEvents::REDIRECT_DO_NOT_TRACK,
                new Events\UntrackableUrlsEvent($this->emailEntity)
            );
            $doNotTrack = $event->getDoNotTrackList();
        }

        if (in_array($url, $foundLinks)) {

            return;
        }

        /**
         * Check if the URL is in the do not track list
         *
         * @param      $testUrl
         * @param null $trackableUrl
         * @param null $trackableKey
         *
         * @return bool
         */
        $shouldUrlBeTracked = function ($testUrl, $trackableUrl = null, $trackableKey = null) use ($doNotTrack, &$foundLinks) {
            $track = true;

            if (null === $trackableUrl) {
                $trackableUrl = $testUrl;
            }

            if (null === $trackableKey) {
                $trackableKey = $testUrl;
            }

            foreach ($doNotTrack as $notTrackable) {
                if (preg_match('/'.$notTrackable.'/i', $testUrl)) {
                    $track = false;

                    break;
                }
            }

            if ($track) {
                $foundLinks[$trackableKey] = $trackableUrl;
            }

            return $track;
        };

        /**
         * Extract tokenized query params
         *
         * @param $query
         *
         * @return array
         */
        $parseTokenizedQuery = function ($query) {
            $tokenizedParams   =
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
        };

        // Convert URL
        $tokenizedParams = false;
        $tracked         = false;
        $urlParts        = parse_url($url);

        // Ensure a valid scheme
        if (isset($urlParts['scheme']) && !in_array($urlParts['scheme'], array('http', 'https', 'ftp', 'ftps'))) {

            return;
        }

        // Ensure a applicable URL (rule out URLs as just #)
        if (!isset($urlParts['host']) && !isset($urlParts['path'])) {

            return;
        }

        // Check for a token with a query appended such as {pagelink=1}&key=value
        if (isset($urlParts['path']) && preg_match('/([https?|ftps?]?{.*?})&amp;(.*?)$/', $urlParts['path'], $match)) {
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
            list($tokenizedParams, $untokenizedParams) = $parseTokenizedQuery($urlParts['query']);
            if ($tokenizedParams) {
                // Rebuild the query without the tokenized query params for now
                $urlParts['query'] = $this->buildQuery($untokenizedParams);
            }
        }

        // Check if URL is trackable
        $tokenizedHost = (!isset($urlParts['host']) && isset($urlParts['path'])) ? $urlParts['path'] : $urlParts['host'];
        if (preg_match('/^({\S+?})/', $tokenizedHost, $match)) {
            // Token as URL

            if (!preg_match('/^({\S+?})$/', $tokenizedHost)) {
                // Currently this does not apply to something like "{leadfield=firstname}.com" since that could result in URL per lead

                return;
            }

            // Set the actual token used in the URL
            $token = $match[1];

            // Tokenized hosts shouldn't use a scheme
            $scheme = (!empty($urlParts['scheme'])) ? $urlParts['scheme'] : false;
            unset($urlParts['scheme']);
            $replaceUrl = $this->buildUrl($urlParts);

            // Acknowledge only known tokens
            if (isset($currentTokens[$token])) {
                $tokenUrl = (!empty($urlParts['query'])) ? $currentTokens[$token].'?'.$urlParts['query'] : $currentTokens[$token];
                $tracked  = $shouldUrlBeTracked($token, $tokenUrl, $replaceUrl);

                if ($tracked && $scheme) {
                    // Token has a schema so let's get rid of it before replacing tokens
                    $this->emailTrackedLinkSettings['firstContentReplace'][$scheme.'://'.$tokenizedHost] = $tokenizedHost;
                }
            }
        } else {
            // Regular URL
            $replaceUrl = $this->buildUrl($urlParts);
            $tracked    = $shouldUrlBeTracked($replaceUrl);
        }

        // Append tokenized params to the end of the URL
        if ($tracked && $tokenizedParams) {
            $replaceUrl .= ($this->emailTrackedLinkSettings['usingClickthrough'] ? '&' : '?').$this->buildQuery($tokenizedParams);

            // Store the search/replace
            $this->emailTrackedLinkSettings['firstContentReplace'][$url] = $replaceUrl;
        }
        unset($tokenizedQueryParams, $tokenizedParams, $untokenizedParams);
    }

    /**
     * @param $parts
     *
     * @return string
     */
    private function buildUrl($parts)
    {
        if (function_exists('http_build_url')) {

            return http_build_url($parts);
        } else {
            /**
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
    private function buildQuery(array $queryParts)
    {
        $query = http_build_query($queryParts);

        // http_build_query likely encoded tokens so that has to be fixed so they get replaced
        $query = preg_replace_callback('/%7B(\S+?)%7D/i',
            function ($matches) {
                return urldecode($matches[0]);
            },
            $query
        );

        return $query;
    }
}
