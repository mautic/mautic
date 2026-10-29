<?php

namespace Mautic\LeadBundle\EventListener;

use Mautic\CoreBundle\Event\TokenReplacementEvent;
use Mautic\CoreBundle\Helper\BuilderTokenHelperFactory;
use Mautic\EmailBundle\Event\EmailDisplayEvent;
use Mautic\EmailBundle\Event\EmailOnBuildEvent;
use Mautic\EmailBundle\Event\EmailSendEvent;
use Mautic\EmailBundle\Event\OnEmailAddressTokenReplacementEvent;
use Mautic\LeadBundle\Helper\TokenHelper;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class EmailSubscriber
{
    private static string $contactFieldRegex = '{contactfield=(.*?)}';

    public function __construct(
        private readonly BuilderTokenHelperFactory $builderTokenHelperFactory,
        private readonly TranslatorInterface $translator,
    ) {
    }

    #[\Symfony\Component\EventDispatcher\Attribute\AsEventListener(event: EmailOnBuildEvent::class)]
    public function onEmailBuild(EmailOnBuildEvent $event): void
    {
        $this->addContactFieldTokens($event);
    }

    #[\Symfony\Component\EventDispatcher\Attribute\AsEventListener(event: EmailDisplayEvent::class)]
    public function onEmailDisplay(EmailSendEvent $event): void
    {
        $this->onEmailGenerate($event);
    }

    #[\Symfony\Component\EventDispatcher\Attribute\AsEventListener(event: EmailSendEvent::class)]
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

    #[\Symfony\Component\EventDispatcher\Attribute\AsEventListener(event: OnEmailAddressTokenReplacementEvent::class)]
    public function onEmailAddressReplacement(TokenReplacementEvent $event): void
    {
        $event->setContent(TokenHelper::findLeadTokens($event->getContent(), $event->getLead()->getProfileFields(), true));
    }

    private function addContactFieldTokens(EmailOnBuildEvent $event): void
    {
        $tokenHelper = $this->builderTokenHelperFactory->getBuilderTokenHelper('lead.field', 'lead:fields', 'MauticLeadBundle');
        // the permissions are for viewing contact data, not for managing contact fields
        $tokenHelper->setPermissionSet(['lead:leads:viewown', 'lead:leads:viewother']);

        if (!$event->tokensRequested(self::$contactFieldRegex)) {
            return;
        }

        $tokenFilter = $event->getTokenFilter();
        $filter      = 'label' === $tokenFilter['target'] ? $tokenFilter['filter'] : '';
        $tokens      = $tokenHelper->getTokens(self::$contactFieldRegex, $filter, 'label', 'alias');
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
