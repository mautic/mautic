<?php

namespace Mautic\PageBundle\EventListener;

use Doctrine\DBAL\Connection;
use DOMDocument;
use DOMXPath;
use Mautic\CoreBundle\Form\Type\GatedVideoType;
use Mautic\CoreBundle\Form\Type\SlotButtonType;
use Mautic\CoreBundle\Form\Type\SlotCategoryListType;
use Mautic\CoreBundle\Form\Type\SlotChannelFrequencyType;
use Mautic\CoreBundle\Form\Type\SlotCodeModeType;
use Mautic\CoreBundle\Form\Type\SlotDwcType;
use Mautic\CoreBundle\Form\Type\SlotImageCaptionType;
use Mautic\CoreBundle\Form\Type\SlotImageCardType;
use Mautic\CoreBundle\Form\Type\SlotImageType;
use Mautic\CoreBundle\Form\Type\SlotPreferredChannelType;
use Mautic\CoreBundle\Form\Type\SlotSavePrefsButtonType;
use Mautic\CoreBundle\Form\Type\SlotSegmentListType;
use Mautic\CoreBundle\Form\Type\SlotSeparatorType;
use Mautic\CoreBundle\Form\Type\SlotSocialFollowType;
use Mautic\CoreBundle\Form\Type\SlotSocialShareType;
use Mautic\CoreBundle\Form\Type\SlotSuccessMessageType;
use Mautic\CoreBundle\Form\Type\SlotTextType;
use Mautic\CoreBundle\Helper\BuilderTokenHelperFactory;
use Mautic\CoreBundle\Helper\TemplatingHelper;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\EmailBundle\EmailEvents;
use Mautic\EmailBundle\Event\EmailBuilderEvent;
use Mautic\EmailBundle\Event\EmailSendEvent;
use Mautic\PageBundle\Event as Events;
use Mautic\PageBundle\Helper\TokenHelper;
use Mautic\PageBundle\Model\PageModel;
use Mautic\PageBundle\PageEvents;
use Mautic\PluginBundle\Helper\IntegrationHelper;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Translation\TranslatorInterface;

class BuilderSubscriber implements EventSubscriberInterface
{
    /**
     * @var TokenHelper
     */
    private $tokenHelper;

    /**
     * @var IntegrationHelper
     */
    private $integrationHelper;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var CorePermissions
     */
    private $security;

    /**
     * @var TemplatingHelper
     */
    private $templating;

    /**
     * @var BuilderTokenHelperFactory
     */
    private $builderTokenHelperFactory;

    /**
     * @var PageModel
     */
    private $pageModel;
    private $pageTokenRegex      = '{pagelink=(.*?)}';
    private $dwcTokenRegex       = '{dwc=(.*?)}';
    private $langBarRegex        = '{langbar}';
    private $shareButtonsRegex   = '{sharebuttons}';
    private $titleRegex          = '{pagetitle}';
    private $descriptionRegex    = '{pagemetadescription}';

    const segmentListRegex  = '{segmentlist}';
    const categoryListRegex = '{categorylist}';
    const channelfrequency  = '{channelfrequency}';
    const preferredchannel  = '{preferredchannel}';
    const saveprefsRegex    = '{saveprefsbutton}';
    const successmessage    = '{successmessage}';
    const identifierToken   = '{leadidentifier}';

    /**
     * BuilderSubscriber constructor.
     */
    public function __construct(
        CorePermissions $security,
        TokenHelper $tokenHelper,
        IntegrationHelper $integrationHelper,
        PageModel $pageModel,
        BuilderTokenHelperFactory $builderTokenHelperFactory,
        TranslatorInterface $translator,
        Connection $connection,
        TemplatingHelper $templating
    ) {
        $this->security                  = $security;
        $this->tokenHelper               = $tokenHelper;
        $this->integrationHelper         = $integrationHelper;
        $this->pageModel                 = $pageModel;
        $this->builderTokenHelperFactory = $builderTokenHelperFactory;
        $this->translator                = $translator;
        $this->connection                = $connection;
        $this->templating                = $templating;
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
     */
    public function onPageBuild(Events\PageBuilderEvent $event)
    {
        $tokenHelper = $this->builderTokenHelperFactory->getBuilderTokenHelper('page');

        if ($event->abTestWinnerCriteriaRequested()) {
            //add AB Test Winner Criteria
            $bounceRate = [
                'group'    => 'mautic.page.abtest.criteria',
                'label'    => 'mautic.page.abtest.criteria.bounce',
                'event'    => PageEvents::ON_DETERMINE_BOUNCE_RATE_WINNER,
            ];
            $event->addAbTestWinnerCriteria('page.bouncerate', $bounceRate);

            $dwellTime = [
                'group'    => 'mautic.page.abtest.criteria',
                'label'    => 'mautic.page.abtest.criteria.dwelltime',
                'event'    => PageEvents::ON_DETERMINE_DWELL_TIME_WINNER,
            ];
            $event->addAbTestWinnerCriteria('page.dwelltime', $dwellTime);
        }

        if ($event->tokensRequested([$this->pageTokenRegex, $this->dwcTokenRegex])) {
            $event->addTokensFromHelper($tokenHelper, $this->pageTokenRegex, 'title', 'id', true);

            // add only filter based dwc tokens
            $dwcTokenHelper = $this->builderTokenHelperFactory->getBuilderTokenHelper('dynamicContent', 'dynamiccontent:dynamiccontents');
            $expr           = $this->connection->getExpressionBuilder()->andX('e.is_campaign_based <> 1 and e.slot_name is not null');
            $tokens         = $dwcTokenHelper->getTokens(
                $this->dwcTokenRegex,
                '',
                'name',
                'slot_name',
                $expr
            );
            $event->addTokens(is_array($tokens) ? $tokens : []);

            $event->addTokens(
                $event->filterTokens(
                    [
                        $this->langBarRegex      => $this->translator->trans('mautic.page.token.lang'),
                        $this->shareButtonsRegex => $this->translator->trans('mautic.page.token.share'),
                        $this->titleRegex        => $this->translator->trans('mautic.core.title'),
                        $this->descriptionRegex  => $this->translator->trans('mautic.page.form.metadescription'),
                        self::segmentListRegex   => $this->translator->trans('mautic.page.form.segmentlist'),
                        self::categoryListRegex  => $this->translator->trans('mautic.page.form.categorylist'),
                        self::preferredchannel   => $this->translator->trans('mautic.page.form.preferredchannel'),
                        self::channelfrequency   => $this->translator->trans('mautic.page.form.channelfrequency'),
                        self::saveprefsRegex     => $this->translator->trans('mautic.page.form.saveprefs'),
                        self::successmessage     => $this->translator->trans('mautic.page.form.successmessage'),
                        self::identifierToken    => $this->translator->trans('mautic.page.form.leadidentifier'),
                    ]
                )
            );
        }

        if ($event->slotTypesRequested()) {
            $event->addSlotType(
                'text',
                $this->translator->trans('mautic.core.slot.label.text'),
                'font',
                'MauticCoreBundle:Slots:text.html.php',
                SlotTextType::class,
                1000
            );
            $event->addSlotType(
                'image',
                $this->translator->trans('mautic.core.slot.label.image'),
                'image',
                'MauticCoreBundle:Slots:image.html.php',
                SlotImageType::class,
                900
            );
            $event->addSlotType(
                'imagecard',
                $this->translator->trans('mautic.core.slot.label.imagecard'),
                'id-card-o',
                'MauticCoreBundle:Slots:imagecard.html.php',
                SlotImageCardType::class,
                870
            );
            $event->addSlotType(
                'imagecaption',
                $this->translator->trans('mautic.core.slot.label.imagecaption'),
                'image',
                'MauticCoreBundle:Slots:imagecaption.html.php',
                SlotImageCaptionType::class,
                850
            );
            $event->addSlotType(
                'button',
                $this->translator->trans('mautic.core.slot.label.button'),
                'external-link',
                'MauticCoreBundle:Slots:button.html.php',
                SlotButtonType::class,
                800
            );
            $event->addSlotType(
                'socialshare',
                $this->translator->trans('mautic.core.slot.label.socialshare'),
                'share-alt',
                'MauticCoreBundle:Slots:socialshare.html.php',
                SlotSocialShareType::class,
                700
            );
            $event->addSlotType(
                'socialfollow',
                $this->translator->trans('mautic.core.slot.label.socialfollow'),
                'twitter',
                'MauticCoreBundle:Slots:socialfollow.html.php',
                SlotSocialFollowType::class,
                600
            );
            if ($this->security->isGranted(['page:preference_center:editown', 'page:preference_center:editother'], 'MATCH_ONE')) {
                $event->addSlotType(
                    'segmentlist',
                    $this->translator->trans('mautic.core.slot.label.segmentlist'),
                    'list-alt',
                    'MauticCoreBundle:Slots:segmentlist.html.php',
                    SlotSegmentListType::class,
                    590
                );
                $event->addSlotType(
                    'categorylist',
                    $this->translator->trans('mautic.core.slot.label.categorylist'),
                    'bookmark-o',
                    'MauticCoreBundle:Slots:categorylist.html.php',
                    SlotCategoryListType::class,
                    580
                );
                $event->addSlotType(
                    'preferredchannel',
                    $this->translator->trans('mautic.core.slot.label.preferredchannel'),
                    'envelope-o',
                    'MauticCoreBundle:Slots:preferredchannel.html.php',
                    SlotPreferredChannelType::class,
                    570
                );
                $event->addSlotType(
                    'channelfrequency',
                    $this->translator->trans('mautic.core.slot.label.channelfrequency'),
                    'calendar',
                    'MauticCoreBundle:Slots:channelfrequency.html.php',
                    SlotChannelFrequencyType::class,
                    560
                );
                $event->addSlotType(
                    'saveprefsbutton',
                    $this->translator->trans('mautic.core.slot.label.saveprefsbutton'),
                    'floppy-o',
                    'MauticCoreBundle:Slots:saveprefsbutton.html.php',
                    SlotSavePrefsButtonType::class,
                    540
                );

                $event->addSlotType(
                    'successmessage',
                    $this->translator->trans('mautic.core.slot.label.successmessage'),
                    'check',
                    'MauticCoreBundle:Slots:successmessage.html.php',
                    SlotSuccessMessageType::class,
                    540
                );
            }
            $event->addSlotType(
                'codemode',
                $this->translator->trans('mautic.core.slot.label.codemode'),
                'code',
                'MauticCoreBundle:Slots:codemode.html.php',
                SlotCodeModeType::class,
                500
            );
            $event->addSlotType(
                'separator',
                $this->translator->trans('mautic.core.slot.label.separator'),
                'minus',
                'MauticCoreBundle:Slots:separator.html.php',
                SlotSeparatorType::class,
                400
            );
            $event->addSlotType(
                'gatedvideo',
                $this->translator->trans('mautic.core.slot.label.gatedvideo'),
                'video-camera',
                'MauticCoreBundle:Slots:gatedvideo.html.php',
                GatedVideoType::class,
                300
            );
            $event->addSlotType(
                'dwc',
                $this->translator->trans('mautic.core.slot.label.dynamiccontent'),
                'sticky-note-o',
                'MauticCoreBundle:Slots:dwc.html.php',
                SlotDwcType::class,
                200
            );
        }

        if ($event->sectionsRequested()) {
            $event->addSection(
                'one-column',
                $this->translator->trans('mautic.core.slot.label.onecolumn'),
                'file-text-o',
                'MauticCoreBundle:Sections:one-column.html.php',
                null,
                1000
            );
            $event->addSection(
                'two-column',
                $this->translator->trans('mautic.core.slot.label.twocolumns'),
                'columns',
                'MauticCoreBundle:Sections:two-column.html.php',
                null,
                900
            );
            $event->addSection(
                'three-column',
                $this->translator->trans('mautic.core.slot.label.threecolumns'),
                'th',
                'MauticCoreBundle:Sections:three-column.html.php',
                null,
                800
            );
        }
    }

    public function onPageDisplay(Events\PageDisplayEvent $event)
    {
        $content = $event->getContent();
        $page    = $event->getPage();
        $params  = $event->getParams();

        if (false !== strpos($content, $this->langBarRegex)) {
            $langbar = $this->renderLanguageBar($page);
            $content = str_ireplace($this->langBarRegex, $langbar, $content);
        }

        if (false !== strpos($content, $this->shareButtonsRegex)) {
            $buttons = $this->renderSocialShareButtons();
            $content = str_ireplace($this->shareButtonsRegex, $buttons, $content);
        }

        if (false !== strpos($content, $this->titleRegex)) {
            $content = str_ireplace($this->titleRegex, $page->getTitle(), $content);
        }

        if (false !== strpos($content, $this->descriptionRegex)) {
            $content = str_ireplace($this->descriptionRegex, $page->getMetaDescription(), $content);
        }

        if ($page->getIsPreferenceCenter()) {
            // replace slots
            if (count($params)) {
                $dom = new DOMDocument('1.0', 'utf-8');
                $dom->loadHTML(mb_convert_encoding($content, 'HTML-ENTITIES', 'UTF-8'), LIBXML_NOERROR);
                $xpath = new DOMXPath($dom);

                $divContent = $xpath->query('//*[@data-slot="segmentlist"]');
                for ($i = 0; $i < $divContent->length; ++$i) {
                    $slot            = $divContent->item($i);
                    $slot->nodeValue = self::segmentListRegex;
                    $slot->setAttribute('data-prefs-center', '1');
                    $content         = $dom->saveHTML();
                }

                $divContent = $xpath->query('//*[@data-slot="categorylist"]');
                for ($i = 0; $i < $divContent->length; ++$i) {
                    $slot            = $divContent->item($i);
                    $slot->nodeValue = self::categoryListRegex;
                    $slot->setAttribute('data-prefs-center', '1');
                    $content         = $dom->saveHTML();
                }

                $divContent = $xpath->query('//*[@data-slot="preferredchannel"]');
                for ($i = 0; $i < $divContent->length; ++$i) {
                    $slot            = $divContent->item($i);
                    $slot->nodeValue = self::preferredchannel;
                    $slot->setAttribute('data-prefs-center', '1');
                    $content         = $dom->saveHTML();
                }

                $divContent = $xpath->query('//*[@data-slot="channelfrequency"]');
                for ($i = 0; $i < $divContent->length; ++$i) {
                    $slot            = $divContent->item($i);
                    $slot->nodeValue = self::channelfrequency;
                    $slot->setAttribute('data-prefs-center', '1');
                    $content         = $dom->saveHTML();
                }

                $divContent = $xpath->query('//*[@data-slot="saveprefsbutton"]');
                for ($i = 0; $i < $divContent->length; ++$i) {
                    $slot            = $divContent->item($i);
                    $saveButton      = $xpath->query('//*[@data-slot="saveprefsbutton"]//a')->item(0);
                    $slot->nodeValue = self::saveprefsRegex;
                    $slot->setAttribute('data-prefs-center', '1');
                    $content         = $dom->saveHTML();

                    $params['saveprefsbutton'] = [
                        'style'      => $saveButton->getAttribute('style'),
                        'background' => $saveButton->getAttribute('background'),
                    ];
                }

                unset($slot, $xpath, $dom);
            }
            // replace tokens
            if (false !== strpos($content, self::segmentListRegex)) {
                $segmentList = $this->renderSegmentList($params);
                $content     = str_ireplace(self::segmentListRegex, $segmentList, $content);
            }

            if (false !== strpos($content, self::categoryListRegex)) {
                $categoryList = $this->renderCategoryList($params);
                $content      = str_ireplace(self::categoryListRegex, $categoryList, $content);
            }

            if (false !== strpos($content, self::preferredchannel)) {
                $preferredChannel = $this->renderPreferredChannel($params);
                $content          = str_ireplace(self::preferredchannel, $preferredChannel, $content);
            }

            if (false !== strpos($content, self::channelfrequency)) {
                $channelfrequency = $this->renderChannelFrequency($params);
                $content          = str_ireplace(self::channelfrequency, $channelfrequency, $content);
            }

            if (false !== strpos($content, self::saveprefsRegex)) {
                $savePrefs = $this->renderSavePrefs($params);
                $content   = str_ireplace(self::saveprefsRegex, $savePrefs, $content);
            }
            // add form before first block of prefs center
            if (isset($params['startform']) && false !== strpos($content, 'data-prefs-center')) {
                $dom = new DOMDocument('1.0', 'utf-8');
                $dom->loadHTML(mb_convert_encoding($content, 'HTML-ENTITIES', 'UTF-8'), LIBXML_NOERROR);
                $xpath      = new DOMXPath($dom);
                // If use slots
                $divContent = $xpath->query('//*[@data-prefs-center="1"]');
                if (!$divContent->length) {
                    // If use tokens
                    $divContent = $xpath->query('//*[@data-prefs-center-first="1"]');
                }

                if ($divContent->length) {
                    $slot    = $divContent->item(0);
                    $newnode = $dom->createElement('startform');
                    $slot->parentNode->insertBefore($newnode, $slot);
                    $content = $dom->saveHTML();
                    $content = str_replace('<startform></startform>', $params['startform'], $content);
                }
            }

            if (false !== strpos($content, self::successmessage)) {
                $successMessage = $this->renderSuccessMessage($params);
                $content        = str_ireplace(self::successmessage, $successMessage, $content);
            }
        }

        $clickThrough = ['source' => ['page', $page->getId()]];
        $tokens       = $this->tokenHelper->findPageTokens($content, $clickThrough);

        if (count($tokens)) {
            $content = str_ireplace(array_keys($tokens), $tokens, $content);
        }

        $headCloseScripts = $page->getHeadScript();
        if ($headCloseScripts) {
            $content = str_ireplace('</head>', $headCloseScripts."\n</head>", $content);
        }

        $bodyCloseScripts = $page->getFooterScript();
        if ($bodyCloseScripts) {
            $content = str_ireplace('</body>', $bodyCloseScripts."\n</body>", $content);
        }

        $event->setContent($content);
    }

    /**
     * Renders the HTML for the social share buttons.
     *
     * @return string
     */
    private function renderSocialShareButtons()
    {
        static $content = '';

        if (empty($content)) {
            $shareButtons = $this->integrationHelper->getShareButtons();

            $content = "<div class='share-buttons'>\n";
            foreach ($shareButtons as $button) {
                $content .= $button;
            }
            $content .= "</div>\n";

            //load the css into the header by calling the sharebtn_css view
            $this->templating->getTemplating()->render('MauticPageBundle:SubscribedEvents\PageToken:sharebtn_css.html.php');
        }

        return $content;
    }

    /**
     * @return string
     */
    private function getAttributeForFirtSlot()
    {
        return 'data-prefs-center-first="1"';
    }

    /**
     * Renders the HTML for the segment list.
     *
     * @return string
     */
    private function renderSegmentList(array $params = [])
    {
        static $content = '';

        if (empty($content)) {
            $content = "<div class='pref-segmentlist' ".$this->getAttributeForFirtSlot().">\n";
            $content .= $this->templating->getTemplating()->render('MauticCoreBundle:Slots:segmentlist.html.php', $params);
            $content .= "</div>\n";
        }

        return $content;
    }

    /**
     * @return string
     */
    private function renderCategoryList(array $params = [])
    {
        static $content = '';

        if (empty($content)) {
            $content = "<div class='pref-categorylist ' ".$this->getAttributeForFirtSlot().">\n";
            $content .= $this->templating->getTemplating()->render('MauticCoreBundle:Slots:categorylist.html.php', $params);
            $content .= "</div>\n";
        }

        return $content;
    }

    /**
     * @return string
     */
    private function renderPreferredChannel(array $params = [])
    {
        static $content = '';

        if (empty($content)) {
            $content = "<div class='pref-preferredchannel'>\n";
            $content .= $this->templating->getTemplating()->render('MauticCoreBundle:Slots:preferredchannel.html.php', $params);
            $content .= "</div>\n";
        }

        return $content;
    }

    /**
     * @return string
     */
    private function renderChannelFrequency(array $params = [])
    {
        static $content = '';

        if (empty($content)) {
            $content = "<div class='pref-channelfrequency'>\n";
            $content .= $this->templating->getTemplating()->render('MauticCoreBundle:Slots:channelfrequency.html.php', $params);
            $content .= "</div>\n";
        }

        return $content;
    }

    /**
     * @return string
     */
    private function renderSavePrefs(array $params = [])
    {
        static $content = '';

        if (empty($content)) {
            $content = "<div class='pref-saveprefs ' ".$this->getAttributeForFirtSlot().">\n";
            $content .= $this->templating->getTemplating()->render('MauticCoreBundle:Slots:saveprefsbutton.html.php', $params);
            $content .= "</div>\n";
        }

        return $content;
    }

    /**
     * @return string
     */
    private function renderSuccessMessage(array $params = [])
    {
        static $content = '';

        if (empty($content)) {
            $content = "<div class=\"pref-successmessage\">\n";
            $content .= $this->templating->getTemplating()->render('MauticCoreBundle:Slots:successmessage.html.php', $params);
            $content .= "</div>\n";
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
    private function renderLanguageBar($page)
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
                    'url'  => $this->pageModel->generateUrl($parent, false).'?ntrd=1',
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
                        'url'  => $this->pageModel->generateUrl($c, false).'?ntrd=1',
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

            $langbar = $this->templating->getTemplating()->render('MauticPageBundle:SubscribedEvents\PageToken:langbar.html.php', ['pages' => $related]);
        }

        return $langbar;
    }

    public function onEmailBuild(EmailBuilderEvent $event)
    {
        if ($event->tokensRequested([$this->pageTokenRegex])) {
            $tokenHelper = $this->builderTokenHelperFactory->getBuilderTokenHelper('page');
            $event->addTokensFromHelper($tokenHelper, $this->pageTokenRegex, 'title', 'id', true);
        }
    }

    public function onEmailGenerate(EmailSendEvent $event)
    {
        $content      = $event->getContent();
        $plainText    = $event->getPlainText();
        $clickthrough = $event->shouldAppendClickthrough() ? $event->generateClickthrough() : [];
        $tokens       = $this->tokenHelper->findPageTokens($content.$plainText, $clickthrough);

        $event->addTokens($tokens);
    }
}
