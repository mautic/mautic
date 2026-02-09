<?php

namespace Mautic\LeadBundle\EventListener;

use Mautic\CoreBundle\Event\TokenReplacementEvent;
use Mautic\CoreBundle\Helper\BuilderTokenHelperFactory;
use Mautic\EmailBundle\EmailEvents;
use Mautic\EmailBundle\Event\EmailBuilderEvent;
use Mautic\EmailBundle\Event\EmailSendEvent;
use Mautic\LeadBundle\Helper\TokenHelper;
use Mautic\PageBundle\Event\PageBuilderEvent;
use Mautic\PageBundle\PageEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class EmailSubscriber implements EventSubscriberInterface
{
    private static string $contactFieldRegex = '{contactfield=(.*?)}';

    public function __construct(
        private BuilderTokenHelperFactory $builderTokenHelperFactory,
        private TranslatorInterface $translator,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            EmailEvents::EMAIL_ON_BUILD                     => ['onEmailBuild', 0],
            EmailEvents::EMAIL_ON_SEND                      => ['onEmailGenerate', 0],
            EmailEvents::EMAIL_ON_DISPLAY                   => ['onEmailDisplay', 0],
            EmailEvents::ON_EMAIL_ADDRESS_TOKEN_REPLACEMENT => ['onEmailAddressReplacement', 0],
            PageEvents::PAGE_ON_BUILD                       => ['onPageBuild', 0],
        ];
    }

    public function onEmailBuild(EmailBuilderEvent $event): void
    {
        $this->addContactFieldTokens($event);
    }

    public function onPageBuild(PageBuilderEvent $event): void
    {
        $this->addContactFieldTokens($event);
    }

    public function onEmailDisplay(EmailSendEvent $event): void
    {
        $this->onEmailGenerate($event);
    }

    public function onEmailGenerate(EmailSendEvent $event): void
    {
        // Combine all possible content to find tokens across them
        $content = $event->getSubject();
        $content .= $event->getContent();
        $content .= $event->getPlainText();
        $content .= implode(' ', $event->getTextHeaders());

        $lead = $event->getLead();

        $tokenList = TokenHelper::findLeadTokens($content, $lead);
        if (count($tokenList)) {
            $event->addTokens($tokenList);
            unset($tokenList);
        }
    }

    public function onEmailAddressReplacement(TokenReplacementEvent $event): void
    {
        $event->setContent(TokenHelper::findLeadTokens($event->getContent(), $event->getLead()->getProfileFields(), true));
    }

    /**
     * @param EmailBuilderEvent|PageBuilderEvent $event
     */
    private function addContactFieldTokens($event): void
    {
        $tokenHelper = $this->builderTokenHelperFactory->getBuilderTokenHelper('lead.field', 'lead:fields', 'MauticLeadBundle');
        // the permissions are for viewing contact data, not for managing contact fields
        $tokenHelper->setPermissionSet(['lead:leads:viewown', 'lead:leads:viewother']);

        if (!$event->tokensRequested(self::$contactFieldRegex)) {
            return;
        }

        $tokens = $tokenHelper->getTokens(self::$contactFieldRegex, '', 'label', 'alias');
        if (!$tokens) {
            return;
        }

        $contactPrefix = $this->translator->trans('mautic.lead.contact').': ';
        $companyPrefix = $this->translator->trans('mautic.core.company').': ';

        $formatted = [];
        foreach ($tokens as $token => $label) {
            // Detect company fields by token pattern: {contactfield=company*}
            if (preg_match('/\{contactfield=company/i', $token)) {
                // Strip leading "Company " from label to avoid "Company: Company Email"
                $cleanLabel        = preg_replace('/^Company\s+/i', '', $label);
                $formatted[$token] = $companyPrefix.$cleanLabel;
            } else {
                $formatted[$token] = $contactPrefix.$label;
            }
        }
        $event->addTokens($formatted);
    }
}
