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
use Mautic\PageBundle\Event as Events;
use Mautic\PageBundle\PageEvents;
use Mautic\EmailBundle\EmailEvents;
use Mautic\EmailBundle\Event\EmailBuilderEvent;
use Mautic\EmailBundle\Event\EmailSendEvent;
use Mautic\PageBundle\Helper\BuilderTokenHelper;

/**
 * Class BuilderSubscriber
 */
class BuilderSubscriber extends CommonSubscriber
{

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents ()
    {
        return array(
            PageEvents::PAGE_ON_DISPLAY   => array('onPageDisplay', 0),
            PageEvents::PAGE_ON_BUILD     => array('onPageBuild', 0),
            EmailEvents::EMAIL_ON_BUILD   => array('onEmailBuild', 0),
            EmailEvents::EMAIL_ON_SEND    => array('onEmailGenerate', 0),
            EmailEvents::EMAIL_ON_DISPLAY => array('onEmailGenerate', 0)
        );
    }

    /**
     * Add forms to available page tokens
     *
     * @param Events\PageBuilderEvent $event
     */
    public function onPageBuild (Events\PageBuilderEvent $event)
    {
        //add page tokens
        $content = $this->templating->render('MauticPageBundle:SubscribedEvents\PageToken:token.html.php');
        $event->addTokenSection('page.extratokens', 'mautic.page.builder.header.extra', $content);

        //add email tokens
        $tokenHelper = new BuilderTokenHelper($this->factory);
        $event->addTokenSection('page.pagetokens', 'mautic.page.builder.header.index', $tokenHelper->getTokenContent());

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

    /**
     * @param Events\PageDisplayEvent $event
     */
    public function onPageDisplay (Events\PageDisplayEvent $event)
    {
        $content = $event->getContent();
        $page    = $event->getPage();

        if (strpos($content, '{langbar}') !== false) {
            $langbar = $this->renderLanguageBar($page);
            $content = str_ireplace('{langbar}', $langbar, $content);
        }

        if (strpos($content, '{sharebuttons}') !== false) {
            $buttons = $this->renderSocialShareButtons();
            $content = str_ireplace('{sharebuttons}', $buttons, $content);
        }

        $this->renderPageUrl($content, array('source' => array('page', $page->getId())));

        $event->setContent($content);
    }

    /**
     * Renders the HTML for the social share buttons
     *
     * @return string
     */
    protected function renderSocialShareButtons ()
    {
        static $content = "";

        if (empty($content)) {
            $shareButtons = $this->factory->getConnectorIntegrationHelper()->getShareButtons();

            $content = "<div class='share-buttons'>\n";
            foreach ($shareButtons as $network => $content) {
                $content .= $content;
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
    protected function renderLanguageBar ($page)
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
                $trans = $this->translator->trans('mautic.page.lang.' . $lang);
                if ($trans == 'mautic.page.lang.' . $lang)
                    $trans = $lang;
                $related[$parent->getId()] = array(
                    "lang" => $trans,
                    "url"  => $model->generateUrl($parent, false)
                );
                foreach ($children as $c) {
                    $lang  = $c->getLanguage();
                    $trans = $this->translator->trans('mautic.page.lang.' . $lang);
                    if ($trans == 'mautic.page.lang.' . $lang)
                        $trans = $lang;
                    $related[$c->getId()] = array(
                        "lang" => $trans,
                        "url"  => $model->generateUrl($c, false)
                    );
                }
            }

            //sort by language
            uasort($related, function ($a, $b) {
                return strnatcasecmp($a['lang'], $b['lang']);
            });

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
    public function onEmailBuild (EmailBuilderEvent $event)
    {
        //add email tokens
        $tokenHelper = new BuilderTokenHelper($this->factory);
        $event->addTokenSection('page.emailtokens', 'mautic.page.builder.header.index', $tokenHelper->getTokenContent());
    }

    /**
     * @param EmailSendEvent $event
     *
     * @return void
     */
    public function onEmailGenerate (EmailSendEvent $event)
    {
        $content      = $event->getContent();
        $source       = $event->getSource();
        $email        = $event->getEmail();

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

        $this->renderPageUrl($content, $clickthrough);

        $event->setContent($content);
    }

    /**
     * @param       $content
     * @param array $clickthrough
     *
     * @return void
     */
    protected function renderPageUrl (&$content, $clickthrough = array())
    {
        static $pages = array(), $links = array();

        $pagelinkRegex     = '/{pagelink=(.*?)}/';
        $externalLinkRegex = '/{externallink=(.*?)}/';

        /** @var \Mautic\PageBundle\Model\PageModel $pageModel */
        $pageModel = $this->factory->getModel('page');

        /** @var \Mautic\PageBundle\Model\RedirectModel $redirectModel */
        $redirectModel = $this->factory->getModel('page.redirect');

        preg_match_all($pagelinkRegex, $content, $matches);
        if (!empty($matches[1])) {
            foreach ($matches[1] as $match) {
                if (empty($pages[$match])) {
                    $pages[$match] = $pageModel->getEntity($match);
                }

                $url     = ($pages[$match] !== null) ? $pageModel->generateUrl($pages[$match], true, $clickthrough) : '';
                $content = str_ireplace('{pagelink=' . $match . '}', $url, $content);
            }
        }

        preg_match_all($externalLinkRegex, $content, $matches);
        if (!empty($matches[1])) {
            foreach ($matches[1] as $match) {
                if (empty($links[$match])) {
                    $links[$match] = $redirectModel->getRedirect($match, true);
                }

                $url     = ($links[$match] !== null) ? $redirectModel->generateRedirectUrl($links[$match], $clickthrough) : '';
                $content = str_ireplace('{externallink=' . $match . '}', $url, $content);
            }
        }
    }
}
