<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PageBundle\EventListener;

use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\CoreBundle\Form\Type\GatedVideoType;
use Mautic\CoreBundle\Form\Type\SlotTextType;
use Mautic\CoreBundle\Helper\BuilderTokenHelper;
use Mautic\DynamicContentBundle\Model\DynamicContentModel;
use Mautic\EmailBundle\EmailEvents;
use Mautic\EmailBundle\Event\EmailBuilderEvent;
use Mautic\EmailBundle\Event\EmailSendEvent;
use Mautic\PageBundle\Event as Events;
use Mautic\PageBundle\Helper\TokenHelper;
use Mautic\PageBundle\Model\PageModel;
use Mautic\PageBundle\PageEvents;
use Mautic\PluginBundle\Helper\IntegrationHelper;

/**
 * Class BuilderSubscriber.
 */
class BuilderSubscriber extends CommonSubscriber
{
    /**
     * @var TokenHelper
     */
    protected $tokenHelper;

    /**
     * @var IntegrationHelper
     */
    protected $integrationHelper;

    /**
     * @var PageModel
     */
    protected $pageModel;

    protected $pageTokenRegex      = '{pagelink=(.*?)}';
    protected $dwcTokenRegex       = '{dwc=(.*?)}';
    protected $langBarRegex        = '{langbar}';
    protected $shareButtonsRegex   = '{sharebuttons}';
    protected $titleRegex          = '{pagetitle}';
    protected $descriptionRegex    = '{pagemetadescription}';
    protected $emailIsInternalSend = false;
    protected $emailEntity         = null;
    /**
     * @var DynamicContentModel
     */
    private $dynamicContentModel;

    /**
     * BuilderSubscriber constructor.
     *
     * @param TokenHelper         $tokenHelper
     * @param IntegrationHelper   $integrationHelper
     * @param PageModel           $pageModel
     * @param DynamicContentModel $dynamicContentModel
     */
    public function __construct(TokenHelper $tokenHelper, IntegrationHelper $integrationHelper, PageModel $pageModel, DynamicContentModel $dynamicContentModel)
    {
        $this->tokenHelper         = $tokenHelper;
        $this->integrationHelper   = $integrationHelper;
        $this->pageModel           = $pageModel;
        $this->dynamicContentModel = $dynamicContentModel;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            PageEvents::PAGE_ON_DISPLAY   => ['onPageDisplay', 0],
            PageEvents::PAGE_ON_BUILD     => ['onPageBuild', 0],
            EmailEvents::EMAIL_ON_BUILD   => ['onEmailBuild', 0],
            EmailEvents::EMAIL_ON_SEND    => ['onEmailGenerate', 0],
            EmailEvents::EMAIL_ON_DISPLAY => ['onEmailGenerate', 0],
        ];
    }

    /**
     * Add forms to available page tokens.
     *
     * @param Events\PageBuilderEvent $event
     */
    public function onPageBuild(Events\PageBuilderEvent $event)
    {
        $tokenHelper = new BuilderTokenHelper($this->factory, 'page');

        if ($event->abTestWinnerCriteriaRequested()) {
            //add AB Test Winner Criteria
            $bounceRate = [
                'group'    => 'mautic.page.abtest.criteria',
                'label'    => 'mautic.page.abtest.criteria.bounce',
                'callback' => '\Mautic\PageBundle\Helper\AbTestHelper::determineBounceTestWinner',
            ];
            $event->addAbTestWinnerCriteria('page.bouncerate', $bounceRate);

            $dwellTime = [
                'group'    => 'mautic.page.abtest.criteria',
                'label'    => 'mautic.page.abtest.criteria.dwelltime',
                'callback' => '\Mautic\PageBundle\Helper\AbTestHelper::determineDwellTimeTestWinner',
            ];
            $event->addAbTestWinnerCriteria('page.dwelltime', $dwellTime);
        }

        if ($event->tokensRequested([$this->pageTokenRegex, $this->dwcTokenRegex])) {
            $event->addTokensFromHelper($tokenHelper, $this->pageTokenRegex, 'title', 'id', false, true);
            $dwcTokenHelper = new BuilderTokenHelper($this->factory, 'dynamicContent');
            $event->addTokensFromHelper($dwcTokenHelper, $this->dwcTokenRegex);

            $event->addTokens(
                $event->filterTokens(
                    [
                        $this->langBarRegex      => $this->translator->trans('mautic.page.token.lang'),
                        $this->shareButtonsRegex => $this->translator->trans('mautic.page.token.share'),
                        $this->titleRegex        => $this->translator->trans('mautic.core.title'),
                        $this->descriptionRegex  => $this->translator->trans('mautic.page.form.metadescription'),
                    ]
                )
            );
        }

        if ($event->slotTypesRequested()) {
            $event->addSlotType(
                'text',
                'Text',
                'font',
                'MauticCoreBundle:Slots:text.html.php',
                SlotTextType::class,
                1000
            );
            $event->addSlotType(
                'image',
                'Image',
                'image',
                'MauticCoreBundle:Slots:image.html.php',
                'slot_image',
                900
            );
            $event->addSlotType(
                'imagecard',
                'Image Card',
                'id-card-o',
                'MauticCoreBundle:Slots:imagecard.html.php',
                'slot_imagecard',
                870
            );
            $event->addSlotType(
                'imagecaption',
                'Image+Caption',
                'image',
                'MauticCoreBundle:Slots:imagecaption.html.php',
                'slot_imagecaption',
                850
            );
            $event->addSlotType(
                'button',
                'Button',
                'external-link',
                'MauticCoreBundle:Slots:button.html.php',
                'slot_button',
                800
            );
            $event->addSlotType(
                'socialshare',
                'Social Share',
                'share-alt',
                'MauticCoreBundle:Slots:socialshare.html.php',
                'slot_socialshare',
                700
            );
            $event->addSlotType(
                'socialfollow',
                'Social Follow',
                'twitter',
                'MauticCoreBundle:Slots:socialfollow.html.php',
                'slot_socialfollow',
                600
            );
            $event->addSlotType(
                'codemode',
                'Code Mode',
                'code',
                'MauticCoreBundle:Slots:codemode.html.php',
                'slot_codemode',
                500
            );
            $event->addSlotType(
                'separator',
                'Separator',
                'minus',
                'MauticCoreBundle:Slots:separator.html.php',
                'slot_separator',
                400
            );
            $event->addSlotType(
                'gatedvideo',
                'Video',
                'video-camera',
                'MauticCoreBundle:Slots:gatedvideo.html.php',
                GatedVideoType::class,
                600
            );
            $event->addSlotType(
                'dwc',
                'Dynamic Content',
                'sticky-note-o',
                'MauticCoreBundle:Slots:dwc.html.php',
                'slot_dwc',
                700
            );
        }

        if ($event->sectionsRequested()) {
            $event->addSection(
                'one-column',
                'One Column',
                'file-text-o',
                'MauticCoreBundle:Sections:one-column.html.php',
                null,
                1000
            );
            $event->addSection(
                'two-column',
                'Two Columns',
                'columns',
                'MauticCoreBundle:Sections:two-column.html.php',
                null,
                900
            );
            $event->addSection(
                'three-column',
                'Three Columns',
                'th',
                'MauticCoreBundle:Sections:three-column.html.php',
                null,
                800
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

        if (strpos($content, $this->titleRegex) !== false) {
            $content = str_ireplace($this->titleRegex, $page->getTitle(), $content);
        }

        if (strpos($content, $this->descriptionRegex) !== false) {
            $content = str_ireplace($this->descriptionRegex, $page->getMetaDescription(), $content);
        }

        $clickThrough = ['source' => ['page', $page->getId()]];
        $tokens       = $this->tokenHelper->findPageTokens($content, $clickThrough);

        if (count($tokens)) {
            $content = str_ireplace(array_keys($tokens), $tokens, $content);
        }

        $event->setContent($content);
    }

    /**
     * Renders the HTML for the social share buttons.
     *
     * @return string
     */
    protected function renderSocialShareButtons()
    {
        static $content = '';

        if (empty($content)) {
            $shareButtons = $this->integrationHelper->getShareButtons();

            $content = "<div class='share-buttons'>\n";
            foreach ($shareButtons as $network => $button) {
                $content .= $button;
            }
            $content .= "</div>\n";

            //load the css into the header by calling the sharebtn_css view
            $this->templating->render('MauticPageBundle:SubscribedEvents\PageToken:sharebtn_css.html.php');
        }

        return $content;
    }

    /**
     * Renders the HTML for the language bar for a given page.
     *
     * @param $page
     *
     * @return string
     */
    protected function renderLanguageBar($page)
    {
        static $langbar = '';

        if (empty($langbar)) {
            $parent   = $page->getTranslationParent();
            $children = $page->getTranslationChildren();

            //check to see if this page is grouped with another
            if (empty($parent) && empty($children)) {
                return;
            }

            $related = [];

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
                $related[$parent->getId()] = [
                    'lang' => $trans,
                    // Add ntrd to not auto redirect to another language
                    'url' => $this->pageModel->generateUrl($parent, false).'?ntrd=1',
                ];
                foreach ($children as $c) {
                    $lang  = $c->getLanguage();
                    $trans = $this->translator->trans('mautic.page.lang.'.$lang);
                    if ($trans == 'mautic.page.lang.'.$lang) {
                        $trans = $lang;
                    }
                    $related[$c->getId()] = [
                        'lang' => $trans,
                        // Add ntrd to not auto redirect to another language
                        'url' => $this->pageModel->generateUrl($c, false).'?ntrd=1',
                    ];
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

            $langbar = $this->templating->render('MauticPageBundle:SubscribedEvents\PageToken:langbar.html.php', ['pages' => $related]);
        }

        return $langbar;
    }

    /**
     * @param EmailBuilderEvent $event
     */
    public function onEmailBuild(EmailBuilderEvent $event)
    {
        if ($event->tokensRequested([$this->pageTokenRegex])) {
            $tokenHelper = new BuilderTokenHelper($this->factory, 'page');
            $event->addTokensFromHelper($tokenHelper, $this->pageTokenRegex, 'title', 'id', false, true);
        }
    }

    /**
     * @param EmailSendEvent $event
     */
    public function onEmailGenerate(EmailSendEvent $event)
    {
        $content      = $event->getContent();
        $plainText    = $event->getPlainText();
        $clickthrough = ($event->shouldAppendClickthrough()) ? $event->generateClickthrough() : [];

        $this->emailIsInternalSend = $event->isInternalSend();
        $this->emailEntity         = $event->getEmail();

        $tokens = $this->tokenHelper->findPageTokens($content.$plainText, $clickthrough);

        $event->addTokens($tokens);
    }
}
