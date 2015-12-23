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
    private $trackedTokenRegex = '{trackedlink=(.*?)}';
    private $langBarRegex = '{langbar}';
    private $shareButtonsRegex = '{sharebuttons}';
    private $emailIsInternalSend = false;
    private $emailEntity = null;

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
            // Using trackable links which will handle persisting redirect URLs, etc
            list(
                $emailTrackedLinks[$emailId]['tokens'],
                $emailTrackedLinks[$emailId]['contentSearch'],
                $emailTrackedLinks[$emailId]['contentReplace']
                ) = $this->generateTrackedLinkTokens($clickthrough, $event);
        }

        // Generate trackable URLs with lead specific click through
        $tokens = array();
        foreach ($emailTrackedLinks[$emailId]['tokens'] as $url => $link) {
            $tokens['{trackedlink='.$link->getRedirectId().'}'] = $redirectModel->generateRedirectUrl($link, $clickthrough);;
        }

        // For plain text, just do a search/replace
        if ($plainText = $event->getPlainText()) {
            $plainText = str_ireplace($emailTrackedLinks[$emailId]['contentSearch'], $emailTrackedLinks[$emailId]['contentReplace'], $plainText);
            $event->setPlainText($plainText);
        }

        // For HTML, replace only the links; leaving the link text (if a URL) intact
        $content = $event->getContent();
        foreach ($emailTrackedLinks[$emailId]['contentSearch'] as $key => $value) {
            $content = preg_replace(
                '/<a(.*?)href=(["\'])'.preg_quote($value, '/').'\\2(.*?)>/i',
                '<a$1href=$2'.$emailTrackedLinks[$emailId]['contentReplace'][$key].'$2$3>',
                $content
            );
        }

        $event->setContent($content);

        return $tokens;
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
     * @deprecated Since version 1.1; to be removed in 2.0
     *
     * @param $content
     * @param $clickthrough
     *
     * @return array
     */
    protected function generateExternalLinkTokens($content, $clickthrough = array()) {
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
     * @param                $clickthrough
     * @param EmailSendEvent $event
     *
     * @return array
     */
    protected function generateTrackedLinkTokens(
        $clickthrough,
        EmailSendEvent $event
    ) {
        /** @var \Mautic\PageBundle\Model\RedirectModel $redirectModel */
        $redirectModel = $this->factory->getModel('page.redirect');

        // Parse email content for links
        $trackedLinks = $this->convertTrackableLinks($event);

        $contentSearch  =
        $contentReplace = array();

        if (!empty($trackedLinks)) {
            foreach ($trackedLinks as $url => $link) {
                $trackedUrl = $redirectModel->generateRedirectUrl($link, $clickthrough);

                $token          = '{trackedlink='.$link->getRedirectId().'}';
                $contentSearch[]       = $url;
                $contentReplace[]      = $token;
                $tokens[$token] = $trackedUrl;
            }

            // Sort to ensure that URLs that share the same base are appropriately replaced
            arsort($contentSearch);
            $tempReplace = array();
            foreach ($contentSearch as $key => $value) {
                $tempReplace[$key] = $contentReplace[$key];
            }
            $contentReplace = $tempReplace;
        }

        return array($trackedLinks, $contentSearch, $contentReplace);
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
        // Get a list of tokens for the tokenized link conversion
        $currentTokens = $event->getTokens();

        /** @var \Mautic\PageBundle\Model\RedirectModel $redirectModel */
        $redirectModel = $this->factory->getModel('page.redirect');

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
        foreach ($links as $link) {
            $url = $link->getAttribute('href');

            // The editor will have converted & to &amp; but DOMDocument will have converted them back so this must be accounted for
            $url = str_replace('&', '&amp;', $url);

            $this->validateLink($url, $currentTokens, $foundLinks);
        }

        // Process plain text as well
        $plainText = $event->getPlainText();

        if (!empty($plainText)) {
            // Plaintext links
            preg_match_all(
                '@(?<![.*">])\b(?:(?:https?|ftp|file)://|[a-z]\.)[-A-Z0-9+&#/%=~_|$?!:,.]*[A-Z0-9+&#/%=~_|$]@i',
                $plainText,
                $matches
            );

            if (!empty($matches[0])) {
                foreach ($matches[0] as $url) {
                    // Remove anything left on at the end; just in case
                    $url = preg_replace('/^\PL+|\PL\z/', '', trim($url));

                    $this->validateLink($url, $currentTokens, $foundLinks);
                }
            }
        }

        $trackedLinks = array();
        if (!empty($foundLinks)) {
            $links = $redirectModel->getRedirectListByUrls($foundLinks, $this->emailEntity);

            foreach ($links as $url => $link) {
                if (!$link->getId() && !isset($persistEntities[$url])) {
                    $persistEntities[$url] = $link;
                }

                $trackedLinks[$url] = $link;
            }
        }

        if (!empty($persistEntities)) {
            // Save redirect entities
            $redirectModel->getRepository()->saveEntities($persistEntities);
        }

        unset($foundLinks, $links, $persistEntities);

        return $trackedLinks;
    }

    /**
     * Validate link
     *
     * @param $url
     * @param $currentTokens
     * @param $foundLinks
     */
    private function validateLink($url, $currentTokens, &$foundLinks)
    {
        if (in_array($url, $foundLinks)) {

            return;
        }

        // Check for tokenized URLs
        // @todo - remove in 2.0
        if (strpos($url, '{externallink') !== false) {

            return;
        }

        if (stripos($url, 'http://{') !== false || strpos($url, 'https://{') !== false || strpos($url, '{') === 0) {
            $token = str_ireplace(array('https://', 'http://'), '', $url);

            $foundLinks[$url] = $currentTokens[$token];
        } elseif (substr($url, 0, 4) == 'http' || substr($url, 0, 3) == 'ftp') {
            $foundLinks[$url] = $url;
        }
    }
}