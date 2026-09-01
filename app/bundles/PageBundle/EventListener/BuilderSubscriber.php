<?php

namespace Mautic\PageBundle\EventListener;

use Doctrine\DBAL\Connection;
use Mautic\CoreBundle\DTO\TokenFormatOptions;
use Mautic\CoreBundle\Helper\BuilderTokenHelperFactory;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
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
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

final class BuilderSubscriber implements EventSubscriberInterface
{
    private const string pageTokenRegex         = '{pagelink=(.*?)}';

    private const string dwcTokenRegex          = '{dwc=(.*?)}';

    private const string langBarRegex           = '{langbar}';

    private const string shareButtonsRegex      = '{sharebuttons}';

    private const string titleRegex             = '{pagetitle}';

    private const string descriptionRegex       = '{pagemetadescription}';

    public const string brandName                = '{brand=name}';

    public const string segmentListRegex         = '{segmentlist}';

    public const string categoryListRegex        = '{categorylist}';

    public const string channelfrequency         = '{channelfrequency}';

    public const string preferredchannel         = '{preferredchannel}';

    public const string saveprefsRegex           = '{saveprefsbutton}';

    public const string successmessage           = '{successmessage}';

    public const string identifierToken          = '{leadidentifier}';

    public const string saveButtonContainerClass = 'prefs-saveprefs';

    public const string firstSlotAttribute       = ' data-prefs-center-first="1"';

    /**
     * @var array<string,string>
     */
    private array $renderedContentCache = [];

    public function __construct(
        private readonly TokenHelper $tokenHelper,
        private readonly IntegrationHelper $integrationHelper,
        private readonly PageModel $pageModel,
        private readonly BuilderTokenHelperFactory $builderTokenHelperFactory,
        private readonly TranslatorInterface $translator,
        private readonly Connection $connection,
        private readonly Environment $twig,
        private readonly CoreParametersHelper $coreParametersHelper,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            Events\PageDisplayEvent::class   => ['onPageDisplay', 0],
            PageEvents::PAGE_ON_BUILD     => ['onPageBuild', 0],
            EmailEvents::EMAIL_ON_BUILD   => ['onEmailBuild', 0],
            EmailEvents::EMAIL_ON_SEND    => ['onEmailGenerate', 0],
            EmailEvents::EMAIL_ON_DISPLAY => ['onEmailGenerate', 0],
        ];
    }

    public function onEmailBuild(EmailBuilderEvent $event): void
    {
        if ($event->tokensRequested([self::pageTokenRegex])) {
            $tokenHelper = $this->builderTokenHelperFactory->getBuilderTokenHelper('page');
            $tokenFilter = $event->getTokenFilter();
            $tokens      = $tokenHelper->getFormattedTokens(
                self::pageTokenRegex,
                TokenFormatOptions::linkWithId('mautic.page.token.pagelink', self::pageTokenRegex),
                'label' === $tokenFilter['target'] ? $tokenFilter['filter'] : '',
                'title',
                'id'
            );
            if ([] !== $tokens) {
                $event->addTokens($tokens);
            }
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

        if ($event->tokensRequested([self::pageTokenRegex, self::dwcTokenRegex])) {
            $tokenFilter = $event->getTokenFilter();
            $labelFilter = 'label' === $tokenFilter['target'] ? $tokenFilter['filter'] : '';
            $tokens      = $tokenHelper->getFormattedTokens(
                self::pageTokenRegex,
                TokenFormatOptions::linkWithId('mautic.page.token.pagelink', self::pageTokenRegex),
                $labelFilter,
                'title'
            );
            if ([] !== $tokens) {
                $event->addTokens($tokens);
            }

            // add only filter based dwc tokens
            $dwcTokenHelper = $this->builderTokenHelperFactory->getBuilderTokenHelper('dynamicContent', 'dynamiccontent:dynamiccontents');
            $expr           = $this->connection->createExpressionBuilder()->and('e.is_campaign_based <> 1 and e.slot_name is not null');
            $dwcTokens      = $dwcTokenHelper->getFormattedTokens(
                self::dwcTokenRegex,
                TokenFormatOptions::simplePrefix('mautic.page.token.dwc'),
                $labelFilter,
                'name',
                'slot_name',
                $expr
            );
            if ([] !== $dwcTokens) {
                $event->addTokens($dwcTokens);
            }

            $thisPagePrefix = $this->translator->trans('mautic.page.token.thispage').': ';
            $event->addTokens(
                $event->filterTokens(
                    [
                        self::langBarRegex      => $thisPagePrefix.$this->translator->trans('mautic.page.token.lang'),
                        self::shareButtonsRegex => $thisPagePrefix.$this->translator->trans('mautic.page.token.share'),
                        self::titleRegex        => $thisPagePrefix.$this->translator->trans('mautic.core.title'),
                        self::brandName         => $thisPagePrefix.$this->translator->trans('mautic.core.token.brand_name'),
                        self::descriptionRegex  => $thisPagePrefix.$this->translator->trans('mautic.page.form.metadescription'),
                        self::segmentListRegex  => $thisPagePrefix.$this->translator->trans('mautic.page.form.segmentlist'),
                        self::categoryListRegex => $thisPagePrefix.$this->translator->trans('mautic.page.form.categorylist'),
                        self::preferredchannel  => $thisPagePrefix.$this->translator->trans('mautic.page.form.preferredchannel'),
                        self::channelfrequency  => $thisPagePrefix.$this->translator->trans('mautic.page.form.channelfrequency'),
                        self::saveprefsRegex    => $thisPagePrefix.$this->translator->trans('mautic.page.form.saveprefs'),
                        self::successmessage    => $thisPagePrefix.$this->translator->trans('mautic.page.form.successmessage'),
                        self::identifierToken   => $thisPagePrefix.$this->translator->trans('mautic.page.form.leadidentifier'),
                    ]
                )
            );
        }
    }

    public function onPageDisplay(Events\PageDisplayEvent $event): void
    {
        if (empty($content = $event->getContent())) {
            return;
        }

        $page    = $event->getPage();
        $params  = $event->getParams();
        $content = $this->replaceCommonTokens($content, $page);

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
        return str_ireplace([
            self::langBarRegex,
            self::shareButtonsRegex,
            self::titleRegex,
            self::brandName,
            self::descriptionRegex,
        ], [
            str_contains($content, self::langBarRegex) ? $this->renderLanguageBar($page) : '',
            str_contains($content, self::shareButtonsRegex) ? $this->renderSocialShareButtons() : '',
            str_contains($content, self::titleRegex) ? $page->getTitle() : '',
            str_contains($content, self::brandName) ? $this->coreParametersHelper->get('brand_name') : '',
            str_contains($content, self::descriptionRegex) ? $page->getMetaDescription() : '',
        ], $content);
    }

    /**
     * @param array<string,mixed> $params
     */
    private function handlePreferenceCenterReplacements(string $content, array $params): string
    {
        $xpath = $this->createDOMXPathForContent($content);

        $content = $this->replacePreferenceCenterTokens($xpath->document->saveHTML(), $params);

        return $this->wrapPreferenceCenterInFormTag($content, $params);
    }

    /**
     * @param array<string,mixed> $params
     */
    private function replacePreferenceCenterTokens(string $content, array $params): string
    {
        return str_ireplace([
            self::segmentListRegex,
            self::categoryListRegex,
            self::preferredchannel,
            self::channelfrequency,
            self::saveprefsRegex,
            self::successmessage,
        ], [
            str_contains($content, self::segmentListRegex) ? $this->renderSegmentList($params) : '',
            str_contains($content, self::categoryListRegex) ? $this->renderCategoryList($params) : '',
            str_contains($content, self::preferredchannel) ? $this->renderPreferredChannel($params) : '',
            str_contains($content, self::channelfrequency) ? $this->renderChannelFrequency($params) : '',
            str_contains($content, self::saveprefsRegex) ? $this->renderSavePrefs($params) : '',
            str_contains($content, self::successmessage) ? $this->renderSuccessMessage($params) : '',
        ], $content);
    }

    /**
     * @param mixed[] $templateParams
     */
    private function renderTemplate(string $templateName, array $templateParams, string $wrapperTemplate = '', string ...$wrapperTemplateValues): string
    {
        if (!empty($this->renderedContentCache[$templateName])) {
            return $this->renderedContentCache[$templateName];
        }

        $content = trim($this->twig->render($templateName, $templateParams));

        if ($wrapperTemplate) {
            // If the content is not empty, ensure that the $wrapperTemplate contains a place to put it.
            if (!empty($content) && !str_contains($wrapperTemplate, '{templateContent}')) {
                throw new \InvalidArgumentException('Your $wrapperTemplate must contain the string {templateContent} where you want to insert the rendered template content.');
            }

            $content = str_replace('{templateContent}', $content, sprintf($wrapperTemplate, ...$wrapperTemplateValues));
        }

        return $this->renderedContentCache[$templateName] = $content;
    }

    private function renderSocialShareButtons(): string
    {
        return $this->renderTemplate(
            '@MauticPage/SubscribedEvents/PageToken/sharebtn_css.html.twig',
            [],
            '<div class="share-buttons">%s</div>',
            implode('', $this->integrationHelper->getShareButtons())
        );
    }

    private function renderSegmentList(array $params): string
    {
        return $this->renderTemplate(
            '@MauticCore/Slots/segmentlist.html.twig',
            $params,
            '<div class="pref-segmentlist"%s>{templateContent}</div>',
            self::firstSlotAttribute
        );
    }

    private function renderCategoryList(array $params): string
    {
        return $this->renderTemplate(
            '@MauticCore/Slots/categorylist.html.twig',
            $params,
            '<div class="pref-categorylist"%s>{templateContent}</div>',
            self::firstSlotAttribute
        );
    }

    private function renderPreferredChannel(array $params): string
    {
        return $this->renderTemplate(
            '@MauticCore/Slots/preferredchannel.html.twig',
            $params,
            '<div class="pref-preferredchannel">{templateContent}</div>'
        );
    }

    private function renderChannelFrequency(array $params): string
    {
        return $this->renderTemplate(
            '@MauticCore/Slots/channelfrequency.html.twig',
            $params,
            '<div class="pref-channelfrequency"%s>{templateContent}</div>',
            self::firstSlotAttribute
        );
    }

    private function renderSavePrefs(array $params): string
    {
        return $this->renderTemplate(
            '@MauticCore/Slots/saveprefsbutton.html.twig',
            $params,
            '<div class="%s"%s>{templateContent}</div>',
            self::saveButtonContainerClass,
            self::firstSlotAttribute
        );
    }

    /**
     * @param array<string,mixed> $params
     */
    private function renderSuccessMessage(array $params): string
    {
        return $this->renderTemplate(
            '@MauticCore/Slots/successmessage.html.twig',
            $params
        );
    }

    private function renderLanguageBar(Page $page): string
    {
        return $this->renderTemplate(
            '@MauticPage/SubscribedEvents/PageToken/langbar.html.twig',
            ['pages' => $this->getRelatedPagesForLanguageBar($page)]
        );
    }

    /**
     * @return array<int,mixed[]>
     */
    private function getRelatedPagesForLanguageBar(Page $page): array
    {
        $related  = [];
        $parent   = $page->getTranslationParent();
        $children = $page->getTranslationChildren();

        if (!$parent instanceof \Mautic\CoreBundle\Entity\TranslationEntityInterface && !$children instanceof \Doctrine\Common\Collections\Collection) {
            return $related;
        }

        // If this page has a parent, then fetch the children from the parent
        if ($parent instanceof \Mautic\CoreBundle\Entity\TranslationEntityInterface) {
            $children = $parent->getTranslationChildren();
        } else {
            // Otherwise this is the parent page.
            $parent = $page;
        }

        if (!$children instanceof \Doctrine\Common\Collections\Collection) {
            return $related;
        }

        if ($parent instanceof Page) {
            $related[$parent->getId()] = $this->buildRelatedArrayForPage($parent);
        }

        foreach ($children as $child) {
            $related[$child->getId()] = $this->buildRelatedArrayForPage($child);
        }

        uasort($related, fn ($a, $b): int => strnatcasecmp($a['lang'], $b['lang']));

        return $related;
    }

    /**
     * @return array<string,string>
     */
    private function buildRelatedArrayForPage(Page $page): array
    {
        $language   = $page->getLanguage();
        $translated = $this->translator->trans('mautic.page.lang.'.$language);

        if ($translated === 'mautic.page.lang.'.$language) {
            $translated = $language;
        }

        return [
            'lang' => $translated,
            // Add ntrd to not auto redirect to another language
            'url'  => $this->pageModel->generateUrl($page, false).'?ntrd=1',
        ];
    }

    private function createDOMXPathForContent(string $content): \DOMXPath
    {
        $domDocument = new \DOMDocument('1.0', 'utf-8');
        $domDocument->loadHTML(mb_encode_numericentity($content, [0x80, 0x10FFFF, 0, 0xFFFFF], 'UTF-8'), LIBXML_NOERROR);

        return new \DOMXPath($domDocument);
    }

    /**
     * @param mixed[] $params
     */
    private function wrapPreferenceCenterInFormTag(string $content, array $params): string
    {
        if (!isset($params['startform']) || !str_contains($content, 'data-prefs-center')) {
            return $content;
        }

        $xpath = $this->createDOMXPathForContent($content);
        $node  = $this->getFirstNodeThatContainsAPreferenceCenterToken($xpath);

        if (null === $node) {
            return $content;
        }

        $parentNode = $this->getFirstParentNodeThatContainsAllFormInputs($node);

        $parentNode->insertBefore(new \DOMElement('startform'), $parentNode->firstChild);
        $parentNode->appendChild(new \DOMElement('endform'));

        return str_replace(['<startform></startform>', '<endform></endform>'], [$params['startform'], '</form>'], $xpath->document->saveHTML());
    }

    private function getFirstNodeThatContainsAPreferenceCenterToken(\DOMXPath $xpath): ?\DOMNode
    {
        $nodeList = $xpath->query('//*[@data-prefs-center-first="1"]');

        if (false !== $nodeList) {
            return $nodeList->item(0);
        }

        return null;
    }

    private function getFirstParentNodeThatContainsAllFormInputs(\DOMNode $node): \DOMNode
    {
        $content = implode('', array_map([$node->ownerDocument, 'saveHTML'], iterator_to_array($node->childNodes)));

        // Check if the save button exists in the content. If not, try again with the parentNode.
        if (!str_contains($content, self::saveButtonContainerClass)) {
            if (null === $node->parentNode) {
                throw new \RuntimeException("Can't get parent node of #document. Did you forget to insert a save button in your preference center form?");
            }

            return $this->getFirstParentNodeThatContainsAllFormInputs($node->parentNode);
        }

        return $node;
    }
}
