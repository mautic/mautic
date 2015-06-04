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
use Mautic\CoreBundle\Helper\MailHelper;
use Mautic\EmailBundle\Entity\Email;
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
    private $pageTokenRegex     = '{pagelink=(.*?)}';
    private $externalTokenRegex = '{externallink=(.*?)}';
    private $trackedTokenRegex  = '{trackedlink=(.*?)}';
    private $langBarRegex       = '{langbar}';
    private $shareButtonsRegex  = '{sharebuttons}';
    private $emailTrackedLinks  = array();

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

        $tokens = $this->generateUrlTokens($content, array('source' => array('page', $page->getId())));
        if (!empty($tokens)) {
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
        $content = $event->getContent();
        $source  = $event->getSource();
        $email   = $event->getEmail();

        $clickthrough = array(
            //what entity is sending the email?
            'source' => $source,
            //the email being sent to be logged in page hit if applicable
            'email'  => ($email != null) ? $email->getId() : null
        );
        $lead         = $event->getLead();
        if ($lead !== null) {
            $clickthrough['lead'] = $lead['id'];
        }


        $tokens = $this->generateUrlTokens($content, $clickthrough, (($email === null) ? 0 : $email->getId()), $email, $event);

        $event->addTokens($tokens);
    }

    /**
     * @param                $content
     * @param                $clickthrough
     * @param null           $emailId
     * @param Email          $email
     * @param EmailSendEvent $event
     *
     * @return array
     */
    protected function generateUrlTokens($content, $clickthrough, $emailId = null, Email $email = null, EmailSendEvent $event = null)
    {
        if ($emailId !== null && isset($this->emailTrackedLinks[$emailId])) {
            // Tokenization is supported and the links have already been parsed so rebuild tokens from saved links

            /** @var \Mautic\PageBundle\Model\RedirectModel $redirectModel */
            $redirectModel = $this->factory->getModel('page.redirect');

            foreach ($this->emailTrackedLinks[$emailId] as $url => $link) {
                $trackedUrl = $redirectModel->generateRedirectUrl($link, $clickthrough);

                if (strpos($url, '{') === 0) {
                    // pageurl, externallink, or trackedlink tokens
                    $tokens[$url] = $trackedUrl;
                } else {
                    $tokens['{trackedlink='.$link->getRedirectId().'}'] = $trackedUrl;
                }
            }
        } else {
            $trackedLinks = $tokens = $persistEntities = array();

            $this->generatePageTokens($content, $clickthrough, $tokens, $persistEntities, $trackedLinks, $emailId, $email);

            $this->generateExternalLinkTokens($content, $clickthrough, $tokens, $persistEntities, $trackedLinks, $emailId, $email);

            if ($emailId !== null) {
                $this->generateTrackedLinkTokens($content, $clickthrough, $tokens, $persistEntities, $trackedLinks, $emailId, $email, $event);
            } elseif (!empty($persistEntities)) {
                /** @var \Mautic\PageBundle\Model\RedirectModel $redirectModel */
                $redirectModel = $this->factory->getModel('page.redirect');

                $redirectModel->getRepository()->saveEntities($persistEntities);
            }
        }

        return $tokens;
    }

    /**
     * @param       $content
     * @param       $clickthrough
     * @param       $tokens
     * @param       $persistEntities
     * @param       $trackedLinks
     * @param null  $emailId
     * @param Email $email
     */
    protected function generatePageTokens($content, $clickthrough, &$tokens, &$persistEntities, &$trackedLinks, $emailId = null, Email $email = null)
    {
        /** @var \Mautic\PageBundle\Model\PageModel $pageModel */
        $pageModel = $this->factory->getModel('page');

        /** @var \Mautic\PageBundle\Model\RedirectModel $redirectModel */
        $redirectModel = $this->factory->getModel('page.redirect');

        $pagelinkRegex = '/'.$this->pageTokenRegex.'/';
        preg_match_all($pagelinkRegex, $content, $matches);

        if (!empty($matches[1])) {
            $foundTokens = array();
            foreach ($matches[1] as $key => $pageId) {
                $token = $matches[0][$key];
                if (!empty($tokens[$token])) {
                    continue;
                }

                $page = $pageModel->getEntity($pageId);

                if (!$page) {
                    continue;
                }

                if ($emailId !== null) {
                    // Emails will have clickthroughs tracked separately so just generate the URL
                    if (!in_array($token, $foundTokens) && !in_array($token, $trackedLinks)) {
                        $foundTokens[$token] = $pageModel->generateUrl($page, true);
                    }
                } else {
                    $tokens[$token] = $pageModel->generateUrl($page, true, $clickthrough);
                }
            }

            if (!empty($foundTokens)) {
                $links = $redirectModel->getRedirectListByUrls($foundTokens, $email);

                foreach ($links as $token => $link) {
                    if (!$link->getId() && !isset($persistEntities[$token])) {
                        $persistEntities[$token] = $link;
                    }

                    $trackedLinks[$token] = $link;
                }
            }
        }
    }

    /**
     * @param       $content
     * @param       $clickthrough
     * @param       $tokens
     * @param       $persistEntities
     * @param       $trackedLinks
     * @param null  $emailId
     * @param Email $email
     *
     * @deprecated Since version 1.1
     */
    protected function generateExternalLinkTokens($content, $clickthrough, &$tokens, &$persistEntities, &$trackedLinks, $emailId = null, Email $email = null)
    {
        /** @var \Mautic\PageBundle\Model\RedirectModel $redirectModel */
        $redirectModel = $this->factory->getModel('page.redirect');

        $externalLinkRegex = '/'.$this->externalTokenRegex.'/';
        preg_match_all($externalLinkRegex, $content, $matches);

        if (!empty($matches[1])) {
            $foundTokens = array();
            foreach ($matches[1] as $key => $match) {
                $token = $matches[0][$key];
                if (!empty($tokens[$token])) {
                    continue;
                }

                $foundTokens[$token] = $match;
            }

            $links = $redirectModel->getRedirectListByUrls($foundTokens, $email);
            foreach ($links as $token => $link) {
                if ($emailId !== null) {
                    if (!isset($trackedLinks[$token])) {
                        $trackedLinks[$token] = $link;
                    }
                } else {
                    $tokens[$token] = $redirectModel->generateRedirectUrl($link, $clickthrough);;
                }

                if (!$link->getId() && !isset($persistEntities[$token])) {
                    $persistEntities[$token] = $link;
                }
            }

            unset($foundTokens, $links);
        }
    }

    /**
     * @param                $content
     * @param                $clickthrough
     * @param                $tokens
     * @param                $persistEntities
     * @param                $trackedLinks
     * @param null           $emailId
     * @param Email          $email
     * @param EmailSendEvent $event
     */
    protected function generateTrackedLinkTokens($content, $clickthrough, &$tokens, &$persistEntities, &$trackedLinks, $emailId = null, Email $email = null, EmailSendEvent $event = null)
    {
        /** @var \Mautic\PageBundle\Model\RedirectModel $redirectModel */
        $redirectModel = $this->factory->getModel('page.redirect');

        // Set tokens for urls that were converted to trackable links already
        $trackedLinkRegex = '/'.$this->trackedTokenRegex.'/';
        preg_match_all($trackedLinkRegex, $content, $matches);
        if (!empty($matches[1])) {
            $foundTokens = array();

            foreach ($matches[1] as $key => $match) {
                $token = $matches[0][$key];

                if (!empty($tokens[$token])) {
                    continue;
                }

                $foundTokens[$token] = $match;
            }

            $links = $redirectModel->getRedirectListByIds($foundTokens, $email);

            foreach ($links as $token => $link) {
                $tokens[$token] = $redirectModel->generateRedirectUrl($link, $clickthrough);;
            }

            unset($foundTokens, $links);
        }

        $this->convertTrackableLinks($event, $persistEntities, $trackedLinks, $email);

        if (!empty($persistEntities)) {
            $redirectModel->getRepository()->saveEntities($persistEntities);
        }

        if (!empty($trackedLinks)) {
            $search = $replace = array();

            foreach ($trackedLinks as $url => $link) {
                $trackedUrl = $redirectModel->generateRedirectUrl($link, $clickthrough);

                if (strpos($url, '{') === 0) {
                    // pageurl, externallink, or trackedlink tokens
                    $tokens[$url] = $trackedUrl;

                    // Add search and replace entries to correct editor auto-prepended http:// or https://
                    $search[]  = 'http://' . $url;
                    $replace[] = $url;

                    $search[]  = 'https://' . $url;
                    $replace[] = $url;
                } else {
                    $token = '{trackedlink='.$link->getRedirectId().'}';
                    $search[]       = $url;
                    $replace[]      = $token;
                    $tokens[$token] = $trackedUrl;
                }
            }

            $content = str_ireplace($search, $replace, $content);
            $event->setContent($content);

            if ($plainText = $event->getPlainText()) {
                $plainText = str_ireplace($search, $replace, $plainText);
                $event->setPlainText($plainText);
            }
        }

        $this->emailTrackedLinks[$emailId] = $trackedLinks;
    }

    /**
     * Converts links to trackable links and tokens
     *
     * @param EmailSendEvent $event
     * @param                $persistEntities
     * @param                $trackedLinks
     * @param                $email
     */
    protected function convertTrackableLinks(EmailSendEvent $event, &$persistEntities, &$trackedLinks, Email $email = null)
    {
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

        $foundLinks = array();
        foreach ($links as $link) {
            $url = $link->getAttribute('href');

            // Ensure a valid URL
            if (substr($url, 0, 4) !== 'http' && substr($url, 0, 3) !== 'ftp' && !in_array($url, $foundLinks) && !in_array($url, $trackedLinks)) {
                continue;
            }

            if (stripos($url, 'http://{') !== false || strpos($url, 'https://{') !== false) {
                // The editor appended an URL token with http
                continue;
            }

            $foundLinks[$url] = $url;
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
                    // Remove anything left on at the end; just inc ase
                    $url = preg_replace('/^\PL+|\PL\z/', '', trim($url));

                    // Ensure a valid URL
                    if (substr($url, 0, 4) !== 'http' && substr($url, 0, 3) !== 'ftp' && !in_array($url, $foundLinks) && !in_array($url, $trackedLinks)) {
                        continue;
                    }

                    if (stripos($url, 'http://{') !== false || strpos($url, 'https://{') !== false) {
                        // The editor appended an URL token with http
                        continue;
                    }

                    $foundLinks[$url] = $url;
                }
            }
        }

        if (!empty($foundLinks)) {
            $links = $redirectModel->getRedirectListByUrls($foundLinks, $email);

            foreach ($links as $url => $link) {
                if (!$link->getId() && !isset($persistEntities[$url])) {
                    $persistEntities[$url] = $link;
                }

                $trackedLinks[$url] = $link;
            }
        }

        unset($foundLinks, $links);
    }
}
