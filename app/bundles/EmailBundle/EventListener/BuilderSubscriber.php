<?php

namespace Mautic\EmailBundle\EventListener;

use Doctrine\Persistence\Mapping\MappingException;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\EmojiHelper;
use Mautic\EmailBundle\EmailEvents;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Event\EmailBuilderEvent;
use Mautic\EmailBundle\Event\EmailSendEvent;
use Mautic\EmailBundle\Helper\MailHashHelper;
use Mautic\EmailBundle\Model\EmailModel;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\PageBundle\Entity\Redirect;
use Mautic\PageBundle\Entity\Trackable;
use Mautic\PageBundle\Model\RedirectModel;
use Mautic\PageBundle\Model\TrackableModel;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class BuilderSubscriber implements EventSubscriberInterface
{
    /**
     * @var array<string, array{array{string, string}, Trackable[]|Redirect[]}>
     */
    private array $convertedContent = [];

    public function __construct(
        private CoreParametersHelper $coreParametersHelper,
        private EmailModel $emailModel,
        private TrackableModel $pageTrackableModel,
        private RedirectModel $pageRedirectModel,
        private TranslatorInterface $translator,
        private MailHashHelper $mailHash,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            EmailEvents::EMAIL_ON_BUILD => ['onEmailBuild', 0],
            EmailEvents::EMAIL_ON_SEND  => [
                ['fixEmailAccessibility', 10000],
                ['onEmailGenerate', 0],
                // Ensure this is done last in order to catch all tokenized URLs
                ['convertUrlsToTokens', -9999],
            ],
            EmailEvents::EMAIL_ON_DISPLAY => [
                ['fixEmailAccessibility', 10000],
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
            '{brand=name}'       => $this->translator->trans('mautic.core.token.brand_name'),
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
            '{dnc_url}'         => $this->translator->trans('mautic.email.token.unsubscribe_all_url'),
            '{resubscribe_url}' => $this->translator->trans('mautic.email.token.resubscribe_url'),
            '{webview_url}'     => $this->translator->trans('mautic.email.token.webview_url'),
        ];
        if ($event->tokensRequested(array_keys($tokens))) {
            $event->addTokens(
                $event->filterTokens($tokens)
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

        // Get email
        $toEmail = null;
        if (is_array($lead) && array_key_exists('email', $lead) && is_string($lead['email'])) {
            $toEmail = $lead['email'];
        } elseif ($lead instanceof Lead && is_string($lead->getEmail())) {
            $toEmail = $lead->getEmail();
        }

        // Get email hash
        $unsubscribeHash = null;
        if ($toEmail) {
            $unsubscribeHash = $this->mailHash->getEmailHash($toEmail);
        }

        if (null == $idHash) {
            // Generate a bogus idHash to prevent errors for routes that may include it
            $idHash = uniqid();
        }

        $unsubscribeText = $this->coreParametersHelper->get('unsubscribe_text');
        if (!$unsubscribeText) {
            $unsubscribeText = $this->translator->trans('mautic.email.unsubscribe.text', ['%link%' => '|URL|']);
        }

        // We will replace tokens in unsubscribe text too
        $unsubscribeText = \Mautic\LeadBundle\Helper\TokenHelper::findLeadTokens($unsubscribeText, $lead, true);
        $unsubscribeText = str_replace('|URL|', $this->emailModel->buildUrl('mautic_email_unsubscribe', ['idHash' => $idHash, 'urlEmail' => $toEmail, 'secretHash' => $unsubscribeHash]), $unsubscribeText);
        $event->addToken('{unsubscribe_text}', EmojiHelper::toHtml($unsubscribeText));
        $event->addToken('{unsubscribe_url}', $this->emailModel->buildUrl('mautic_email_unsubscribe', ['idHash' => $idHash, 'urlEmail' => $toEmail, 'secretHash' => $unsubscribeHash]));
        $event->addToken('{dnc_url}', $this->emailModel->buildUrl('mautic_email_unsubscribe_all', ['idHash' => $idHash, 'urlEmail' => $toEmail, 'secretHash' => $unsubscribeHash]));
        $event->addToken('{resubscribe_url}', $this->emailModel->buildUrl('mautic_email_resubscribe', ['idHash' => $idHash]));

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
        $event->addToken('{brand=name}', (string) $this->coreParametersHelper->get('brand_name'));
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
     * Parses the content for URLs and replaces them for trackables.
     *
     * @param ?int $emailId
     *
     * @return Trackable[]|Redirect[]
     *
     * @throws MappingException
     */
    private function parseContentForUrls(EmailSendEvent $event, $emailId): array
    {
        $cacheKey = $event->getContentHash().'-'.$emailId;

        // Prevent parsing the exact same content over and over
        if (!isset($this->convertedContent[$cacheKey])) {
            [$content, $trackables] = $this->pageTrackableModel->parseContentForTrackables(
                [$event->getContent(), $event->getPlainText()],
                $event->getTokens(),
                ($emailId) ? 'email' : null,
                $emailId
            );
            $this->convertedContent[$cacheKey] = [$content, $trackables];

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
        }

        [$content, $trackables] = $this->convertedContent[$cacheKey];
        [$html, $text]          = $content;

        if ($html) {
            $event->setContent($html);
        }
        if ($text) {
            $event->setPlainText($text);
        }

        return $trackables;
    }
}
