<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\EventListener;

use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\CoreBundle\Helper\BuilderTokenHelper;
use Mautic\CoreBundle\Token\TokenReplacerInterface;
use Mautic\EmailBundle\EmailEvents;
use Mautic\EmailBundle\Event\EmailBuilderEvent;
use Mautic\EmailBundle\Event\EmailSendEvent;

/**
 * Class EmailSubscriber.
 */
class EmailSubscriber extends CommonSubscriber
{
    /**
     * @var TokenReplacerInterface
     */
    private $contactTokenReplacer;

    /**
     * EmailSubscriber constructor.
     *
     * @param TokenReplacerInterface $contactTokenReplacer
     */
    public function __construct(TokenReplacerInterface $contactTokenReplacer)
    {
        $this->contactTokenReplacer = $contactTokenReplacer;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            EmailEvents::EMAIL_ON_BUILD   => ['onEmailBuild', 0],
            EmailEvents::EMAIL_ON_SEND    => ['onEmailGenerate', 0],
            EmailEvents::EMAIL_ON_DISPLAY => ['onEmailDisplay', 0],
        ];
    }

    /**
     * @param EmailBuilderEvent $event
     */
    public function onEmailBuild(EmailBuilderEvent $event)
    {
        $tokenHelper = new BuilderTokenHelper($this->factory, 'lead.field', 'lead:fields', 'MauticLeadBundle');
        // the permissions are for viewing contact data, not for managing contact fields
        $tokenHelper->setPermissionSet(['lead:leads:viewown', 'lead:leads:viewother']);

        $regex = $this->contactTokenReplacer->getRegex();
        if ($event->tokensRequested(reset($regex))) {
            $event->addTokensFromHelper($tokenHelper, reset($regex), 'label', 'alias', true);
        }
    }

    /**
     * @param EmailSendEvent $event
     */
    public function onEmailDisplay(EmailSendEvent $event)
    {
        $this->onEmailGenerate($event);
    }

    /**
     * @param EmailSendEvent $event
     */
    public function onEmailGenerate(EmailSendEvent $event)
    {
        // Combine all possible content to find tokens across them
        $content = $event->getSubject();
        $content .= $event->getContent();
        $content .= $event->getPlainText();
        $content .= implode(' ', $event->getTextHeaders());
        $tokenList = $this->contactTokenReplacer->getTokens($content, $event->getLead());
        if (count($tokenList)) {
            $event->addTokens($tokenList);
            unset($tokenList);
        }
    }
}
