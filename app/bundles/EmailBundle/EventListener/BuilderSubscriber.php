<?php

namespace Mautic\EmailBundle\EventListener;

use Doctrine\Persistence\Mapping\MappingException;
use Mautic\CoreBundle\Form\Type\SlotButtonType;
use Mautic\CoreBundle\Form\Type\SlotCodeModeType;
use Mautic\CoreBundle\Form\Type\SlotDynamicContentType;
use Mautic\CoreBundle\Form\Type\SlotImageCaptionType;
use Mautic\CoreBundle\Form\Type\SlotImageCardType;
use Mautic\CoreBundle\Form\Type\SlotSeparatorType;
use Mautic\CoreBundle\Form\Type\SlotSocialFollowType;
use Mautic\CoreBundle\Form\Type\SlotTextType;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\EmojiHelper;
use Mautic\EmailBundle\EmailEvents;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Event\EmailBuilderEvent;
use Mautic\EmailBundle\Event\EmailSendEvent;
use Mautic\EmailBundle\Model\EmailModel;
use Mautic\PageBundle\Entity\Redirect;
use Mautic\PageBundle\Entity\Trackable;
use Mautic\PageBundle\Model\RedirectModel;
use Mautic\PageBundle\Model\TrackableModel;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class BuilderSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private CoreParametersHelper $coreParametersHelper,
        private EmailModel $emailModel,
        private TrackableModel $pageTrackableModel,
        private RedirectModel $pageRedirectModel,
        private TranslatorInterface $translator
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            EmailEvents::EMAIL_ON_BUILD => ['onEmailBuild', 0],
            EmailEvents::EMAIL_ON_SEND  => [
                ['fixEmailAccessibility', 0],
                ['onEmailGenerate', 0],
                // Ensure this is done last in order to catch all tokenized URLs
                ['convertUrlsToTokens', -9999],
            ],
            EmailEvents::EMAIL_ON_DISPLAY => [
                ['fixEmailAccessibility', 0],
                ['onEmailGenerate', 0],
                // Ensure this is done last in order to catch all tokenized URLs
                ['convertUrlsToTokens', -9999],
            ],
        ];
    }

    public function onEmailBuild(EmailBuilderEvent $event): void
    {
        if ($event->abTestWinnerCriteriaRequested()) {
            // add AB Test Winner Criteria
            $openRate = [
                'group'    => 'mautic.email.stats',
                'label'    => 'mautic.email.abtest.criteria.open',
                'event'    => EmailEvents::ON_DETERMINE_OPEN_RATE_WINNER,
            ];
            $event->addAbTestWinnerCriteria('email.openrate', $openRate);

            $clickThrough = [
                'group'    => 'mautic.email.stats',
                'label'    => 'mautic.email.abtest.criteria.clickthrough',
                'event'    => EmailEvents::ON_DETERMINE_CLICKTHROUGH_RATE_WINNER,
            ];
            $event->addAbTestWinnerCriteria('email.clickthrough', $clickThrough);
        }

        $tokens = [
            '{unsubscribe_text}' => $this->translator->trans('mautic.email.token.unsubscribe_text'),
            '{webview_text}'     => $this->translator->trans('mautic.email.token.webview_text'),
            '{signature}'        => $this->translator->trans('mautic.email.token.signature'),
            '{subject}'          => $this->translator->trans('mautic.email.subject'),
        ];

        if ($event->tokensRequested(array_keys($tokens))) {
            $event->addTokens(
                $event->filterTokens($tokens)
            );
        }

        // these should not allow visual tokens
        $tokens = [
            '{unsubscribe_url}' => $this->translator->trans('mautic.email.token.unsubscribe_url'),
            '{webview_url}'     => $this->translator->trans('mautic.email.token.webview_url'),
        ];
        if ($event->tokensRequested(array_keys($tokens))) {
            $event->addTokens(
                $event->filterTokens($tokens)
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
                SlotImageCardType::class,
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
                'socialfollow',
                $this->translator->trans('mautic.core.slot.label.socialfollow'),
                'twitter',
                '@MauticCore/Slots/socialfollow.html.twig',
                SlotSocialFollowType::class,
                600
            );
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
                'dynamicContent',
                $this->translator->trans('mautic.core.slot.label.dynamiccontent'),
                'tag',
                '@MauticCore/Slots/dynamiccontent.html.twig',
                SlotDynamicContentType::class,
                300
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

    public function fixEmailAccessibility(EmailSendEvent $event): void
    {
        if ($event->isDynamicContentParsing() || !$event->getEmail() instanceof Email) {
            // prevent a loop
            return;
        }

        $content = $event->getContent();
        $subject = $event->getEmail()->getSubject();

        // Add the empty <head/> tag if it's missing.
        if (empty(preg_match('#<\s*?head\b[^>]*>(.*?)</head\b[^>]*>#s', $content, $matches))) {
            $content = str_replace('<body', '<head></head><body', $content);
        }

        // Add the <title/> tag with email subject value into the <head/> tag if it's missing.
        $content = preg_replace_callback(
            "/<title>(.*?)<\/title>/is",
            fn ($matches) => empty(trim($matches[1])) ? "<title>{$subject}</title>" : $matches[0],
            $content,
            -1,
            $fixed
        );

        if (!$fixed) {
            $content = str_replace('</head>', "<title>{$subject}</title></head>", $content);
        }

        // Add the lang attribute to the <html/> tag if it's missing.
        $locale = empty($event->getEmail()->getLanguage()) ? $this->coreParametersHelper->get('locale') : $event->getEmail()->getLanguage();
        preg_match_all("~<html.*lang\s*=\s*[\"']([^\"']+)[\"'][^>]*>~i", $content, $matches);
        if (empty($matches[1])) {
            $content = str_replace('<html', '<html lang="'.$locale.'"', $content);
        }

        $event->setContent($content);
    }

    public function onEmailGenerate(EmailSendEvent $event): void
    {
        $idHash = $event->getIdHash();
        $lead   = $event->getLead();
        $email  = $event->getEmail();

        if (null == $idHash) {
            // Generate a bogus idHash to prevent errors for routes that may include it
            $idHash = uniqid();
        }

        $unsubscribeText = $this->coreParametersHelper->get('unsubscribe_text');
        if (!$unsubscribeText) {
            $unsubscribeText = $this->translator->trans('mautic.email.unsubscribe.text', ['%link%' => '|URL|']);
        }
        $unsubscribeText = str_replace('|URL|', $this->emailModel->buildUrl('mautic_email_unsubscribe', ['idHash' => $idHash]), $unsubscribeText);
        $event->addToken('{unsubscribe_text}', EmojiHelper::toHtml($unsubscribeText));

        $event->addToken('{unsubscribe_url}', $this->emailModel->buildUrl('mautic_email_unsubscribe', ['idHash' => $idHash]));

        $webviewText = $this->coreParametersHelper->get('webview_text');
        if (!$webviewText) {
            $webviewText = $this->translator->trans('mautic.email.webview.text', ['%link%' => '|URL|']);
        }
        $webviewText = str_replace('|URL|', $this->emailModel->buildUrl('mautic_email_webview', ['idHash' => $idHash]), $webviewText);
        $event->addToken('{webview_text}', EmojiHelper::toHtml($webviewText));

        // Show public email preview if the lead is not known to prevent 404
        if (empty($lead['id']) && $email) {
            $event->addToken('{webview_url}', $this->emailModel->buildUrl('mautic_email_preview', ['objectId' => $email->getId()]));
        } else {
            $event->addToken('{webview_url}', $this->emailModel->buildUrl('mautic_email_webview', ['idHash' => $idHash]));
        }

        $signatureText = (string) $this->coreParametersHelper->get('default_signature_text');
        $fromName      = $this->coreParametersHelper->get('mailer_from_name');
        $signatureText = str_replace('|FROM_NAME|', $fromName, nl2br($signatureText));
        $event->addToken('{signature}', EmojiHelper::toHtml($signatureText));

        $event->addToken('{subject}', EmojiHelper::toHtml($event->getSubject()));
    }

    public function convertUrlsToTokens(EmailSendEvent $event): void
    {
        if ($event->isInternalSend() || $this->coreParametersHelper->get('disable_trackable_urls')) {
            // Don't convert urls
            return;
        }

        $shortenEnabled = $this->coreParametersHelper->get('shortener_email_enable', false);
        $email          = $event->getEmail();
        $emailId        = $email instanceof Email ? $email->getId() : null;
        $utmTags        = $email instanceof Email ? $email->getUtmTags() : [];

        $clickthrough = $event->generateClickthrough();
        $trackables   = $this->parseContentForUrls($event, $emailId);

        /**
         * @var Trackable|Redirect $trackable
         */
        foreach ($trackables as $token => $trackable) {
            $url = ($trackable instanceof Trackable)
                ?
                $this->pageTrackableModel->generateTrackableUrl($trackable, $clickthrough, $shortenEnabled, $utmTags)
                :
                $this->pageRedirectModel->generateRedirectUrl($trackable, $clickthrough, $shortenEnabled, $utmTags);

            $event->addToken($token, $url);
        }
    }

    /**
     * Parses content for URLs and tokens.
     *
     * @param int|null $emailId
     *
     * @return array<mixed>
     *
     * @throws MappingException
     */
    private function parseContentForUrls(EmailSendEvent $event, $emailId): array
    {
        static $convertedContent = [];

        // Prevent parsing the exact same content over and over
        if (!isset($convertedContent[$event->getContentHash()])) {
            $html = $event->getContent();
            $text = $event->getPlainText();

            $contentTokens = $event->getTokens();

            [$content, $trackables] = $this->pageTrackableModel->parseContentForTrackables(
                [$html, $text],
                $contentTokens,
                ($emailId) ? 'email' : null,
                $emailId
            );

            [$html, $text] = $content;
            unset($content);

            if ($html) {
                $event->setContent($html);
            }
            if ($text) {
                $event->setPlainText($text);
            }

            $convertedContent[$event->getContentHash()] = $trackables;

            foreach ($trackables as $trackable) {
                $trackableRepository = $this->pageTrackableModel->getRepository();
                $redirectRepository  = $this->pageRedirectModel->getRepository();

                if ($trackable instanceof Trackable) {
                    $trackableRepository->detachEntity($trackable);
                    $redirectRepository->detachEntity($trackable->getRedirect());
                    $trackableRepository->detachEntities($trackable->getRedirect()->getTrackableList()->toArray());
                } else {
                    $redirectRepository->detachEntity($trackable);
                    $trackableRepository->detachEntities($trackable->getTrackableList()->toArray());
                }
            }

            unset($html, $text, $trackables);
        }

        return $convertedContent[$event->getContentHash()];
    }
}
