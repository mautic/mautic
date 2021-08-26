<?php

namespace Mautic\PageBundle\EventListener;

use Doctrine\DBAL\Connection;
use DOMDocument;
use DOMNode;
use DOMXPath;
use InvalidArgumentException;
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
use Mautic\PageBundle\Entity\Page;
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

    const pageTokenRegex           = '{pagelink=(.*?)}';
    const dwcTokenRegex            = '{dwc=(.*?)}';
    const langBarRegex             = '{langbar}';
    const shareButtonsRegex        = '{sharebuttons}';
    const titleRegex               = '{pagetitle}';
    const descriptionRegex         = '{pagemetadescription}';
    const segmentListRegex         = '{segmentlist}';
    const categoryListRegex        = '{categorylist}';
    const channelfrequency         = '{channelfrequency}';
    const preferredchannel         = '{preferredchannel}';
    const saveprefsRegex           = '{saveprefsbutton}';
    const successmessage           = '{successmessage}';
    const identifierToken          = '{leadidentifier}';
    const saveButtonContainerClass = 'prefs-saveprefs';
    const firstSlotAttribute       = 'data-prefs-center-first="1"';

    private $renderedContentCache  = [];

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

        if ($event->tokensRequested([static::pageTokenRegex, static::dwcTokenRegex])) {
            $event->addTokensFromHelper($tokenHelper, static::pageTokenRegex, 'title', 'id', true);

            // add only filter based dwc tokens
            $dwcTokenHelper = $this->builderTokenHelperFactory->getBuilderTokenHelper('dynamicContent', 'dynamiccontent:dynamiccontents');
            $expr           = $this->connection->getExpressionBuilder()->andX('e.is_campaign_based <> 1 and e.slot_name is not null');
            $tokens         = $dwcTokenHelper->getTokens(
                static::dwcTokenRegex,
                '',
                'name',
                'slot_name',
                $expr
            );
            $event->addTokens(is_array($tokens) ? $tokens : []);

            $event->addTokens(
                $event->filterTokens(
                    [
                        static::langBarRegex      => $this->translator->trans('mautic.page.token.lang'),
                        static::shareButtonsRegex => $this->translator->trans('mautic.page.token.share'),
                        static::titleRegex        => $this->translator->trans('mautic.core.title'),
                        static::descriptionRegex  => $this->translator->trans('mautic.page.form.metadescription'),
                        static::segmentListRegex  => $this->translator->trans('mautic.page.form.segmentlist'),
                        static::categoryListRegex => $this->translator->trans('mautic.page.form.categorylist'),
                        static::preferredchannel  => $this->translator->trans('mautic.page.form.preferredchannel'),
                        static::channelfrequency  => $this->translator->trans('mautic.page.form.channelfrequency'),
                        static::saveprefsRegex    => $this->translator->trans('mautic.page.form.saveprefs'),
                        static::successmessage    => $this->translator->trans('mautic.page.form.successmessage'),
                        static::identifierToken   => $this->translator->trans('mautic.page.form.leadidentifier'),
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
        $page    = $event->getPage();
        $params  = $event->getParams();
        $content = $this->replaceCommonTokens($event->getContent(), $page);

        if ($page->getIsPreferenceCenter()) {
            $content = $this->handlePreferenceCenterReplacements($content, $params);
        }

        if ($tokens = $this->tokenHelper->findPageTokens($content, ['source' => ['page', $page->getId()]])) {
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

    private function replaceCommonTokens(string $content, Page $page): string
    {
        return str_ireplace(
            [
                static::langBarRegex,
                static::shareButtonsRegex,
                static::titleRegex,
                static::descriptionRegex,
            ],
            [
                $this->renderLanguageBar($page),
                $this->renderSocialShareButtons(),
                $page->getTitle(),
                $page->getMetaDescription(),
            ],
            $content
        );
    }

    private function handlePreferenceCenterReplacements(string $content, array $params)
    {
        // replace slots
        if (count($params)) {
            $dom = new DOMDocument('1.0', 'utf-8');
            $dom->loadHTML(mb_convert_encoding($content, 'HTML-ENTITIES', 'UTF-8'), LIBXML_NOERROR);
            $xpath = new DOMXPath($dom);

            $divContent = $xpath->query('//*[@data-slot="segmentlist"]');
            for ($i = 0; $i < $divContent->length; ++$i) {
                $slot            = $divContent->item($i);
                $slot->nodeValue = static::segmentListRegex;
                $slot->setAttribute('data-prefs-center', '1');
                $content = $dom->saveHTML();
            }

            $divContent = $xpath->query('//*[@data-slot="categorylist"]');
            for ($i = 0; $i < $divContent->length; ++$i) {
                $slot            = $divContent->item($i);
                $slot->nodeValue = static::categoryListRegex;
                $slot->setAttribute('data-prefs-center', '1');
                $content = $dom->saveHTML();
            }

            $divContent = $xpath->query('//*[@data-slot="preferredchannel"]');
            for ($i = 0; $i < $divContent->length; ++$i) {
                $slot            = $divContent->item($i);
                $slot->nodeValue = static::preferredchannel;
                $slot->setAttribute('data-prefs-center', '1');
                $content = $dom->saveHTML();
            }

            $divContent = $xpath->query('//*[@data-slot="channelfrequency"]');
            for ($i = 0; $i < $divContent->length; ++$i) {
                $slot            = $divContent->item($i);
                $slot->nodeValue = static::channelfrequency;
                $slot->setAttribute('data-prefs-center', '1');
                $content = $dom->saveHTML();
            }

            $divContent = $xpath->query('//*[@data-slot="saveprefsbutton"]');
            for ($i = 0; $i < $divContent->length; ++$i) {
                $slot            = $divContent->item($i);
                $saveButton      = $xpath->query('//*[@data-slot="saveprefsbutton"]//a')->item(0);
                $slot->nodeValue = static::saveprefsRegex;
                $slot->setAttribute('data-prefs-center', '1');
                $content = $dom->saveHTML();

                $params['saveprefsbutton'] = [
                    'style'      => $saveButton->getAttribute('style'),
                    'background' => $saveButton->getAttribute('background'),
                ];
            }

            unset($slot, $xpath, $dom);
        }

        $content = $this->replacePreferenceCenterTokens($content, $params);

        // Find the parent dom node that contains all the preference center inputs, and wrap it in a <form> tag
        if (isset($params['startform']) && false !== strpos($content, 'data-prefs-center')) {
            $dom = new DOMDocument('1.0', 'utf-8');
            $dom->loadHTML(mb_convert_encoding($content, 'HTML-ENTITIES', 'UTF-8'), LIBXML_NOERROR);
            $xpath = new DOMXPath($dom);
            // If use slots
            $divContent = $xpath->query('//*[@data-prefs-center="1"]');
            if (!$divContent->length) {
                // If use tokens
                $divContent = $xpath->query('//*[@data-prefs-center-first="1"]');
            }

            // keep walking up the nodes until we find the parent node that includes the first slot AND the `save-prefs` button
            if ($divContent->length) {
                $parentNode = $this->getOutermostNodeThatContainsAllFormInputs($divContent->item(0));

                $parentNode->insertBefore($dom->createElement('startform'), $parentNode->firstChild);
                $parentNode->appendChild($dom->createElement('endform'));

                $content = str_replace(['<startform></startform>', '<endform></endform>'], [$params['startform'], '</form>'], $dom->saveHTML());
            }
        }

        $content = str_ireplace(static::successmessage, $this->renderSuccessMessage($params), $content);

        return $content;
    }

    /**
     * Replace tokens in content with their proper values.
     */
    private function replacePreferenceCenterTokens(string $content, array $params): string
    {
        return str_ireplace(
            [
                static::segmentListRegex,
                static::categoryListRegex,
                static::preferredchannel,
                static::channelfrequency,
                static::saveprefsRegex,
            ],
            [
                $this->renderSegmentList($params),
                $this->renderCategoryList($params),
                $this->renderPreferredChannel($params),
                $this->renderChannelFrequency($params),
                $this->renderSavePrefs($params),
            ],
            $content
        );
    }

    /**
     * Traverses the node parents until it finds the one which
     * includes the first slot AND the save prefs button, then
     * returns that node.
     */
    private function getOutermostNodeThatContainsAllFormInputs(DOMNode $node): DOMNode
    {
        $content = implode(array_map([$node->ownerDocument, 'saveHTML'], iterator_to_array($node->childNodes)));

        // Check if the save button exists in the content. If not, try again with the parentNode.
        if (false === strpos($content, static::saveButtonContainerClass)) {
            return $this->getOutermostNodeThatContainsAllFormInputs($node->parentNode);
        }

        return $node;
    }

    private function renderTemplate(string $templateName, array $templateParams, string $wrapperTemplate = '', ...$wrapperTemplateValues): string
    {
        if (!empty($this->renderedContentCache[$templateName])) {
            return $this->renderedContentCache[$templateName];
        }

        $content = $this->templating->getTemplating()->render($templateName, $templateParams) ?: '';

        if ($wrapperTemplate) {
            // If the content is not empty, ensure that the $wrapperTemplate contains a place to put it.
            if (!empty($content) && false === strpos($wrapperTemplate, '{templateContent}')) {
                throw new InvalidArgumentException('Your $wrapperTemplate must contain the string {templateContent} where you want to insert the rendered template content.');
            }

            $content = str_replace('{templateContent}', $content, sprintf($wrapperTemplate, ...$wrapperTemplateValues));
        }

        return $this->renderedContentCache[$templateName] = $content;
    }

    private function renderSocialShareButtons(): string
    {
        return $this->renderTemplate(
            'MauticPageBundle:SubscribedEvents\PageToken:sharebtn_css.html.php',
            [],
            '<div class="share-buttons">%s</div>',
            implode($this->integrationHelper->getShareButtons())
        );
    }

    private function renderSegmentList(array $params): string
    {
        return $this->renderTemplate(
            'MauticCoreBundle:Slots:segmentlist.html.php',
            $params,
            '<div class="pref-segmentlist %s">{templateContent}</div>',
            static::firstSlotAttribute
        );
    }

    private function renderCategoryList(array $params): string
    {
        return $this->renderTemplate(
            'MauticCoreBundle:Slots:categorylist.html.php',
            $params,
            '<div class="pref-categorylist %s">{templateContent}</div>',
            static::firstSlotAttribute
        );
    }

    private function renderPreferredChannel(array $params): string
    {
        return $this->renderTemplate(
            'MauticCoreBundle:Slots:preferredchannel.html.php',
            $params,
            '<div class="pref-preferredchannel">{templateContent}</div>'
        );
    }

    private function renderChannelFrequency(array $params): string
    {
        return $this->renderTemplate(
            'MauticCoreBundle:Slots:channelfrequency.html.php',
            $params,
            '<div class="pref-channelfrequency">{templateContent}</div>'
        );
    }

    private function renderSavePrefs(array $params): string
    {
        return $this->renderTemplate(
            'MauticCoreBundle:Slots:saveprefsbutton.html.php',
            $params,
            '<div class="%s %s">{templateContent}</div>',
            static::saveButtonContainerClass,
            static::firstSlotAttribute
        );
    }

    private function renderSuccessMessage(array $params): string
    {
        return $this->renderTemplate(
            'MauticCoreBundle:Slots:successmessage.html.php',
            $params,
            '<div class="pref-successmessage">{templateContent}</div>'
        );
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
