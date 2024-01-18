<?php

namespace Mautic\PageBundle\EventListener;

use Doctrine\DBAL\Connection;
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
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Twig\Helper\AssetsHelper;
use Mautic\EmailBundle\EmailEvents;
use Mautic\EmailBundle\Event\EmailBuilderEvent;
use Mautic\EmailBundle\Event\EmailSendEvent;
use Mautic\PageBundle\Event as Events;
use Mautic\PageBundle\Helper\TokenHelper;
use Mautic\PageBundle\Model\PageModel;
use Mautic\PageBundle\PageEvents;
use Mautic\PluginBundle\Helper\IntegrationHelper;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

class BuilderSubscriber implements EventSubscriberInterface
{
    private string $pageTokenRegex      = '{pagelink=(.*?)}';

    private string $dwcTokenRegex       = '{dwc=(.*?)}';

    private string $langBarRegex        = '{langbar}';

    private string $shareButtonsRegex   = '{sharebuttons}';

    private string $titleRegex          = '{pagetitle}';

    private string $descriptionRegex    = '{pagemetadescription}';

    public const segmentListRegex  = '{segmentlist}';

    public const categoryListRegex = '{categorylist}';

    public const channelfrequency  = '{channelfrequency}';

    public const preferredchannel  = '{preferredchannel}';

    public const saveprefsRegex    = '{saveprefsbutton}';

    public const successmessage    = '{successmessage}';

    public const identifierToken   = '{leadidentifier}';

    public function __construct(
        private CorePermissions $security,
        private TokenHelper $tokenHelper,
        private IntegrationHelper $integrationHelper,
        private PageModel $pageModel,
        private BuilderTokenHelperFactory $builderTokenHelperFactory,
        private TranslatorInterface $translator,
        private Connection $connection,
        private Environment $twig,
        private AssetsHelper $assetsHelper
    ) {
    }

    public static function getSubscribedEvents(): array
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
    public function onPageBuild(Events\PageBuilderEvent $event): void
    {
        $tokenHelper = $this->builderTokenHelperFactory->getBuilderTokenHelper('page');

        if ($event->abTestWinnerCriteriaRequested()) {
            // add AB Test Winner Criteria
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
            $expr           = $this->connection->getExpressionBuilder()->and('e.is_campaign_based <> 1 and e.slot_name is not null');
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
                '@MauticCore/Slots/text.html.twig',
                SlotTextType::class,
                1000
            );
            $event->addSlotType(
                'image',
                $this->translator->trans('mautic.core.slot.label.image'),
                'image',
                '@MauticCore/Slots/image.html.twig',
                SlotImageType::class,
                900
            );
            $event->addSlotType(
                'imagecard',
                $this->translator->trans('mautic.core.slot.label.imagecard'),
                'id-card-o',
                '@MauticCore/Slots/imagecard.html.twig',
                SlotImageCardType::class,
                870
            );
            $event->addSlotType(
                'imagecaption',
                $this->translator->trans('mautic.core.slot.label.imagecaption'),
                'image',
                '@MauticCore/Slots/imagecaption.html.twig',
                SlotImageCaptionType::class,
                850
            );
            $event->addSlotType(
                'button',
                $this->translator->trans('mautic.core.slot.label.button'),
                'external-link',
                '@MauticCore/Slots/button.html.twig',
                SlotButtonType::class,
                800
            );
            $event->addSlotType(
                'socialshare',
                $this->translator->trans('mautic.core.slot.label.socialshare'),
                'share-alt',
                '@MauticCore/Slots/socialshare.html.twig',
                SlotSocialShareType::class,
                700
            );
            $event->addSlotType(
                'socialfollow',
                $this->translator->trans('mautic.core.slot.label.socialfollow'),
                'twitter',
                '@MauticCore/Slots/socialfollow.html.twig',
                SlotSocialFollowType::class,
                600
            );
            if ($this->security->isGranted(['page:preference_center:editown', 'page:preference_center:editother'], 'MATCH_ONE')) {
                $event->addSlotType(
                    'segmentlist',
                    $this->translator->trans('mautic.core.slot.label.segmentlist'),
                    'list-alt',
                    '@MauticCore/Slots/segmentlist.html.twig',
                    SlotSegmentListType::class,
                    590
                );
                $event->addSlotType(
                    'categorylist',
                    $this->translator->trans('mautic.core.slot.label.categorylist'),
                    'bookmark-o',
                    '@MauticCore/Slots/categorylist.html.twig',
                    SlotCategoryListType::class,
                    580
                );
                $event->addSlotType(
                    'preferredchannel',
                    $this->translator->trans('mautic.core.slot.label.preferredchannel'),
                    'envelope-o',
                    '@MauticCore/Slots/preferredchannel.html.twig',
                    SlotPreferredChannelType::class,
                    570
                );
                $event->addSlotType(
                    'channelfrequency',
                    $this->translator->trans('mautic.core.slot.label.channelfrequency'),
                    'calendar',
                    '@MauticCore/Slots/channelfrequency.html.twig',
                    SlotChannelFrequencyType::class,
                    560
                );
                $event->addSlotType(
                    'saveprefsbutton',
                    $this->translator->trans('mautic.core.slot.label.saveprefsbutton'),
                    'floppy-o',
                    '@MauticCore/Slots/saveprefsbutton.html.twig',
                    SlotSavePrefsButtonType::class,
                    540
                );

                $event->addSlotType(
                    'successmessage',
                    $this->translator->trans('mautic.core.slot.label.successmessage'),
                    'check',
                    '@MauticCore/Slots/successmessage.html.twig',
                    SlotSuccessMessageType::class,
                    540
                );
            }
            $event->addSlotType(
                'codemode',
                $this->translator->trans('mautic.core.slot.label.codemode'),
                'code',
                '@MauticCore/Slots/codemode.html.twig',
                SlotCodeModeType::class,
                500
            );
            $event->addSlotType(
                'separator',
                $this->translator->trans('mautic.core.slot.label.separator'),
                'minus',
                '@MauticCore/Slots/separator.html.twig',
                SlotSeparatorType::class,
                400
            );
            $event->addSlotType(
                'gatedvideo',
                $this->translator->trans('mautic.core.slot.label.gatedvideo'),
                'video-camera',
                '@MauticCore/Slots/gatedvideo.html.twig',
                GatedVideoType::class,
                300
            );
            $event->addSlotType(
                'dwc',
                $this->translator->trans('mautic.core.slot.label.dynamiccontent'),
                'sticky-note-o',
                '@MauticCore/Slots/dwc.html.twig',
                SlotDwcType::class,
                200
            );
        }

        if ($event->sectionsRequested()) {
            $event->addSection(
                'one-column',
                $this->translator->trans('mautic.core.slot.label.onecolumn'),
                'file-text-o',
                '@MauticCore/Sections/one-column.html.twig',
                null,
                1000
            );
            $event->addSection(
                'two-column',
                $this->translator->trans('mautic.core.slot.label.twocolumns'),
                'columns',
                '@MauticCore/Sections/two-column.html.twig',
                null,
                900
            );
            $event->addSection(
                'three-column',
                $this->translator->trans('mautic.core.slot.label.threecolumns'),
                'th',
                '@MauticCore/Sections/three-column.html.twig',
                null,
                800
            );
        }
    }

    public function onPageDisplay(Events\PageDisplayEvent $event): void
    {
        $content = $event->getContent();
        $page    = $event->getPage();
        $params  = $event->getParams();

        if (str_contains($content, $this->langBarRegex)) {
            $langbar = $this->renderLanguageBar($page);
            $content = str_ireplace($this->langBarRegex, $langbar, $content);
        }

        if (str_contains($content, $this->shareButtonsRegex)) {
            $buttons = $this->renderSocialShareButtons();
            $content = str_ireplace($this->shareButtonsRegex, $buttons, $content);
        }

        if (str_contains($content, $this->titleRegex)) {
            $content = str_ireplace($this->titleRegex, $page->getTitle(), $content);
        }

        if (str_contains($content, $this->descriptionRegex)) {
            $content = str_ireplace($this->descriptionRegex, $page->getMetaDescription(), $content);
        }

        if ($page->getIsPreferenceCenter()) {
            // replace slots
            if (count($params)) {
                $dom = new \DOMDocument('1.0', 'utf-8');
                $dom->loadHTML(mb_convert_encoding($content, 'HTML-ENTITIES', 'UTF-8'), LIBXML_NOERROR);
                $xpath = new \DOMXPath($dom);

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
            if (str_contains($content, self::segmentListRegex)) {
                $segmentList = $this->renderSegmentList($params);
                $content     = str_ireplace(self::segmentListRegex, $segmentList, $content);
            }

            if (str_contains($content, self::categoryListRegex)) {
                $categoryList = $this->renderCategoryList($params);
                $content      = str_ireplace(self::categoryListRegex, $categoryList, $content);
            }

            if (str_contains($content, self::preferredchannel)) {
                $preferredChannel = $this->renderPreferredChannel($params);
                $content          = str_ireplace(self::preferredchannel, $preferredChannel, $content);
            }

            if (str_contains($content, self::channelfrequency)) {
                $channelfrequency = $this->renderChannelFrequency($params);
                $content          = str_ireplace(self::channelfrequency, $channelfrequency, $content);
            }

            if (str_contains($content, self::saveprefsRegex)) {
                $savePrefs = $this->renderSavePrefs($params);
                $content   = str_ireplace(self::saveprefsRegex, $savePrefs.($params['custom_tag'] ?? ''), $content);
            }
            // add form before first block of prefs center
            if (isset($params['startform']) && str_contains($content, 'data-prefs-center')) {
                $dom = new \DOMDocument('1.0', 'utf-8');
                $dom->loadHTML(mb_convert_encoding($content, 'HTML-ENTITIES', 'UTF-8'), LIBXML_NOERROR);
                $xpath      = new \DOMXPath($dom);
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

                /* Add close form tag before the custom tag to prevent cascading forms
                 * in case there is already an unsubscribe form on the page
                 * that's why we can't use the bodyclose customdeclaration
                 */
                if (!empty($params['form'])) {
                    $formEnd = $this->twig->render('@MauticCore/Default/form_end.html.twig', $params);

                    if (!empty($params['custom_tag'])) {
                        $this->assetsHelper->addCustomDeclaration($formEnd, 'customTag');
                    } else {
                        $this->assetsHelper->addCustomDeclaration($formEnd, 'bodyClose');
                    }
                }
            }

            if (str_contains($content, self::successmessage)) {
                $successMessage = $this->renderSuccessMessage($params);
                $content        = str_ireplace(self::successmessage, $successMessage, $content);
            }
        }

        $clickThrough = ['source' => ['page', $page->getId()]];
        $tokens       = $this->tokenHelper->findPageTokens($content, $clickThrough);

        if ([] !== $tokens) {
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

            // load the css into the header by calling the sharebtn_css view
            $this->twig->render('@MauticPage/SubscribedEvents/PageToken/sharebtn_css.html.twig');
        }

        return $content;
    }

    private function getAttributeForFirtSlot(): string
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
            $content .= $this->twig->render('@MauticCore/Slots/segmentlist.html.twig', $params);
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
            $content .= $this->twig->render('@MauticCore/Slots/categorylist.html.twig', $params);
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
            $content .= $this->twig->render('@MauticCore/Slots/preferredchannel.html.twig', $params);
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
            $content .= $this->twig->render('@MauticCore/Slots/channelfrequency.html.twig', $params);
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
            $content .= $this->twig->render('@MauticCore/Slots/saveprefsbutton.html.twig', $params);
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
            $content .= $this->twig->render('@MauticCore/Slots/successmessage.html.twig', $params);
            $content .= "</div>\n";
        }

        return $content;
    }

    /**
     * Renders the HTML for the language bar for a given page.
     *
     * @return string
     */
    private function renderLanguageBar($page)
    {
        static $langbar = '';

        if (empty($langbar)) {
            $parent   = $page->getTranslationParent();
            $children = $page->getTranslationChildren();

            // check to see if this page is grouped with another
            if (empty($parent) && empty($children)) {
                return;
            }

            $related = [];

            // get a list of associated pages/languages
            if (!empty($parent)) {
                $children = $parent->getTranslationChildren();
            } else {
                $parent = $page; // parent is self
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

            // sort by language
            uasort(
                $related,
                fn ($a, $b): int => strnatcasecmp($a['lang'], $b['lang'])
            );

            if (empty($related)) {
                return;
            }

            $langbar = $this->twig->render('@MauticPage/SubscribedEvents/PageToken/langbar.html.twig', ['pages' => $related]);
        }

        return $langbar;
    }

    public function onEmailBuild(EmailBuilderEvent $event): void
    {
        if ($event->tokensRequested([$this->pageTokenRegex])) {
            $tokenHelper = $this->builderTokenHelperFactory->getBuilderTokenHelper('page');
            $event->addTokensFromHelper($tokenHelper, $this->pageTokenRegex, 'title', 'id', true);
        }
    }

    public function onEmailGenerate(EmailSendEvent $event): void
    {
        $content      = $event->getContent();
        $plainText    = $event->getPlainText();
        $clickthrough = $event->shouldAppendClickthrough() ? $event->generateClickthrough() : [];
        $tokens       = $this->tokenHelper->findPageTokens($content.$plainText, $clickthrough);

        $event->addTokens($tokens);
    }
}
