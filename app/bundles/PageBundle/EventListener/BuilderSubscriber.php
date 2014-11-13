<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PageBundle\EventListener;

use Mautic\ApiBundle\Event\RouteEvent;
use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\PageBundle\Event as Events;
use Mautic\PageBundle\PageEvents;
use Mautic\SocialBundle\Helper\NetworkIntegrationHelper;

/**
 * Class BuilderSubscriber
 *
 * @package Mautic\PageBundle\EventListener
 */
class BuilderSubscriber extends CommonSubscriber
{

    /**
     * @return array
     */
    static public function getSubscribedEvents()
    {
        return array(
            PageEvents::PAGE_ON_DISPLAY    => array('onPageDisplay', 0),
            PageEvents::PAGE_ON_BUILD      => array('onPageBuild', 0)
        );
    }

    /**
     * Add forms to available page tokens
     *
     * @param PageBuilderEvent $event
     */
    public function onPageBuild(Events\PageBuilderEvent $event)
    {
        //add page tokens
        $content = $this->templating->render('MauticPageBundle:SubscribedEvents\PageToken:token.html.php');
        $event->addTokenSection('page.pagetokens', 'mautic.page.page.header.index', $content);

        //add AB Test Winner Criteria
        $bounceRate = array(
            'group'    => 'mautic.page.page.abtest.criteria',
            'label'    => 'mautic.page.page.abtest.criteria.bounce',
            'callback' => '\Mautic\PageBundle\Helper\AbTestHelper::determineBounceTestWinner'
        );
        $event->addAbTestWinnerCriteria('page.bouncerate', $bounceRate);

        $dwellTime = array(
            'group'    => 'mautic.page.page.abtest.criteria',
            'label'    => 'mautic.page.page.abtest.criteria.dwelltime',
            'callback' => '\Mautic\PageBundle\Helper\AbTestHelper::determineDwellTimeTestWinner'
        );
        $event->addAbTestWinnerCriteria('page.dwelltime', $dwellTime);
    }

    /**
     * @param PageEvent $event
     */
    public function onPageDisplay(Events\PageEvent $event)
    {
        $content  = $event->getContent();
        $page     = $event->getPage();

        foreach ($content as $slot => &$html) {
            if (strpos($html, '{langbar}') !== false) {
                $langbar = $this->renderLanguageBar($page);
                $html    = str_ireplace('{langbar}', $langbar, $html);
            }

            if (strpos($html, '{sharebuttons}') !== false) {
                $buttons = $this->renderSocialShareButtons($page, $event->getSlotsHelper());
                $html    = str_ireplace('{sharebuttons}', $buttons, $html);
            }
        }

        $event->setContent($content);
    }

    /**
     * Renders the HTML for the social share buttons
     *
     * @param $page
     *
     * @return string
     */
    protected function renderSocialShareButtons($page, $slotsHelper)
    {
        static $content = "";

        if (empty($content)) {
            $shareButtons = NetworkIntegrationHelper::getShareButtons($this->factory);

            $content = "<div class='share-buttons'>\n";
            foreach ($shareButtons as $network => $html) {
                $content .= $html;
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
            if (empty($parent) && empty($children))
                return;

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
            } else {
                $langbar = $this->templating->render('MauticPageBundle:SubscribedEvents\PageToken:langbar.html.php', array('pages' => $related));
            }
        }

        return $langbar;
    }
}