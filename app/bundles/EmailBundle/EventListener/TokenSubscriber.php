<?php

/*
 * @copyright   2015 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\EventListener;

use Mautic\CoreBundle\Event\TokenReplacementEvent;
use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\EmailBundle\EmailEvents;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Event\EmailSendEvent;
use Mautic\LeadBundle\Entity\Lead;

/**
 * Class TokenSubscriber.
 */
class TokenSubscriber extends CommonSubscriber
{
    use MatchFilterForLeadTrait;

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            EmailEvents::EMAIL_ON_SEND     => ['decodeTokens', 254],
            EmailEvents::EMAIL_ON_DISPLAY  => ['decodeTokens', 254],
            EmailEvents::TOKEN_REPLACEMENT => ['onTokenReplacement', 254],
        ];
    }

    /**
     * @param EmailSendEvent $event
     */
    public function decodeTokens(EmailSendEvent $event)
    {
        // Find and replace encoded tokens for trackable URL conversion
        $content = $event->getContent();
        $content = preg_replace('/(%7B)(.*?)(%7D)/i', '{$2}', $content, -1, $count);
        $event->setContent($content);

        if ($plainText = $event->getPlainText()) {
            $plainText = preg_replace('/(%7B)(.*?)(%7D)/i', '{$2}', $plainText);
            $event->setPlainText($plainText);
        }

        $email = $event->getEmail();
        if ($dynamicContentAsArray = $email instanceof Email ? $email->getDynamicContent() : null) {
            $lead       = $event->getLead();
            $tokens     = $event->getTokens();
            $tokenEvent = new TokenReplacementEvent(
                null, $lead, [
                    'tokens'         => $tokens,
                    'lead'           => null,
                    'dynamicContent' => $dynamicContentAsArray,
                ]
            );
            $this->dispatcher->dispatch(EmailEvents::TOKEN_REPLACEMENT, $tokenEvent);
            $event->addTokens($tokenEvent->getTokens());
        }
    }

    /**
     * @param TokenReplacementEvent $event
     */
    public function onTokenReplacement(TokenReplacementEvent $event)
    {
        $clickthrough = $event->getClickthrough();

        if (!array_key_exists('dynamicContent', $clickthrough)) {
            return;
        }

        $lead      = $event->getLead();
        $tokens    = $clickthrough['tokens'];
        $tokenData = $clickthrough['dynamicContent'];

        if ($lead instanceof Lead) {
            $lead = $lead->getProfileFields();
        }

        foreach ($tokenData as $data) {
            // Default content
            $filterContent = $data['content'];

            foreach ($data['filters'] as $filter) {
                if ($this->matchFilterForLead($filter['filters'], $lead)) {
                    $filterContent = $filter['content'];
                }
            }

            // Replace lead tokens in dynamic content (but no recurrence on dynamic content to avoid infinite loop)
            $emailSendEvent = new EmailSendEvent(
                null,
                [
                    'content' => $filterContent,
                    'email'   => null,
                    'idHash'  => null,
                    'tokens'  => $tokens,
                    'lead'    => $lead,
                ]
            );
            $this->dispatcher->dispatch(EmailEvents::EMAIL_ON_DISPLAY, $emailSendEvent);
            $untokenizedContent = $emailSendEvent->getContent(true);

            $event->addToken('{dynamiccontent="'.$data['tokenName'].'"}', $untokenizedContent);
        }
    }
}
