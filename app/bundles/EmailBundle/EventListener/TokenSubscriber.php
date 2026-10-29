<?php

namespace Mautic\EmailBundle\EventListener;

use Mautic\CoreBundle\Event\TokenReplacementEvent;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Event\EmailDisplayEvent;
use Mautic\EmailBundle\Event\EmailSendEvent;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadListRepository;
use Mautic\LeadBundle\Helper\PrimaryCompanyHelper;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

final readonly class TokenSubscriber
{
    use MatchFilterForLeadTrait;
    public function __construct(
        private EventDispatcherInterface $dispatcher,
        private PrimaryCompanyHelper $primaryCompanyHelper,
        private LeadListRepository $segmentRepository,
    ) {
    }
    #[\Symfony\Component\EventDispatcher\Attribute\AsEventListener(
        event: EmailSendEvent::class,
        priority: 254
    )]
    #[\Symfony\Component\EventDispatcher\Attribute\AsEventListener(
        event: EmailDisplayEvent::class,
        priority: 254
    )]
    public function decodeTokens(EmailSendEvent $event): void
    {
        if ($event->isDynamicContentParsing()) {
            // prevent a loop
            return;
        }

        // Find and replace encoded tokens for trackable URL conversion
        $content = $event->getContent();
        $content = $this->urlDecodeTokens($content);
        $event->setContent($content);

        if ($plainText = $event->getPlainText()) {
            $plainText = $this->urlDecodeTokens($plainText);
            $event->setPlainText($plainText);
        }

        $email = $event->getEmail();
        if ($dynamicContentAsArray = $email instanceof Email ? $email->getDynamicContent() : null) {
            $lead       = $event->getLead();
            $tokens     = $event->getTokens();
            $tokenEvent = new TokenReplacementEvent(
                null,
                $lead,
                [
                    'tokens'         => $tokens,
                    'lead'           => null,
                    'dynamicContent' => $dynamicContentAsArray,
                    'idHash'         => $event->getIdHash(),
                ],
                $email,
                $event->isInternalSend()
            );
            $this->dispatcher->dispatch($tokenEvent);
            $event->addTokens($tokenEvent->getTokens());
        }
    }
    #[\Symfony\Component\EventDispatcher\Attribute\AsEventListener(
        event: TokenReplacementEvent::class,
        priority: -254
    )]
    public function onTokenReplacement(TokenReplacementEvent $event): void
    {
        $clickthrough = $event->getClickthrough();

        if (!array_key_exists('dynamicContent', $clickthrough)) {
            return;
        }

        $lead      = $event->getLead();
        $tokens    = $clickthrough['tokens'];
        $tokenData = $clickthrough['dynamicContent'];

        if ($lead instanceof Lead) {
            $lead = $this->primaryCompanyHelper->getProfileFieldsWithPrimaryCompany($lead);
        } else {
            $lead = $this->primaryCompanyHelper->mergePrimaryCompanyWithProfileFields($lead['id'], $lead);
        }

        foreach ($tokenData as $data) {
            // Default content
            $filterContent = $data['content'];

            foreach ($data['filters'] as $filter) {
                if ($this->matchFilterForLead($filter['filters'], $lead)) {
                    $filterContent = $filter['content'];
                    break;
                }
            }

            // Replace lead tokens in dynamic content (but no recurrence on dynamic content to avoid infinite loop)
            $emailSendEvent = new EmailDisplayEvent(
                null,
                [
                    'content' => $filterContent,
                    'email'   => $event->getPassthrough(),
                    'idHash'  => !empty($clickthrough['idHash']) ? $clickthrough['idHash'] : null,
                    'tokens'  => $tokens,
                    'lead'    => $lead,
                ],
                true
            );

            $this->dispatcher->dispatch($emailSendEvent);

            $untokenizedContent = $emailSendEvent->getContent(!$event->isInternalSend());

            $event->addToken('{dynamiccontent="'.$data['tokenName'].'"}', $untokenizedContent);
        }
    }
    private function urlDecodeTokens(string $content): string
    {
        return preg_replace('/(%7B)(.*?)(%3D|=)(.*?)(%7D)/i', '{$2=$4}', $content);
    }
}
