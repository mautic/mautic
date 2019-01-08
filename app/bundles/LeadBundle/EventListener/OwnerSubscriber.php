<?php

/*
 * @copyright   2019 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\EventListener;

use Mautic\EmailBundle\EmailEvents;
use Mautic\EmailBundle\Event\EmailBuilderEvent;
use Mautic\EmailBundle\Event\EmailSendEvent;
use Mautic\LeadBundle\Model\LeadModel;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class OwnerSubscriber implements EventSubscriberInterface
{
    /**
     * @var string
     */
    private static $ownerFieldSprintf = '{ownerfield=%s}';

    /** @var LeadModel */
    private $leadModel;

    public function __construct(LeadModel $leadModel)
    {
        $this->leadModel = $leadModel;
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
        $event->addToken(self::buildToken('email'), 'Owner Email');
        $event->addToken(self::buildToken('first_name'), 'Owner First Name');
        $event->addToken(self::buildToken('last_name'), 'Owner Last Name');
    }

    /**
     * @param EmailSendEvent $event
     */
    public function onEmailDisplay(EmailSendEvent $event)
    {
        $contact = $event->getLead();

        $event->addTokens($this->getGeneratedTokens($contact));
    }

    /**
     * @param EmailSendEvent $event
     */
    public function onEmailGenerate(EmailSendEvent $event)
    {
        $contact = $event->getLead();

        $event->addTokens($this->getGeneratedTokens($contact));
    }

    /**
     * Generates an array of tokens based on the given token.
     *
     * * If contact[owner_id] === 0, then we need fake data
     * * If contact[owner_id] === null, then we should blank out tokens
     * * If contact[owner_id] > 0 AND User exists, then we should fill in tokens
     *
     * @param array $contact
     *
     * @return array
     */
    private function getGeneratedTokens(array $contact)
    {
        if (isset($contact['owner_id']) === false) {
            return $this->getEmptyTokens();
        }

        if ($contact['owner_id'] === 0) {
            return $this->getFakeTokens();
        }

        $owner = $this->leadModel->getRepository()->getLeadOwner($contact['owner_id']);
        if ($owner === false) {
            return $this->getEmptyTokens();
        }

        return [
            self::buildToken('email')      => (string) $owner['email'],
            self::buildToken('first_name') => (string) $owner['first_name'],
            self::buildToken('last_name')  => (string) $owner['last_name'],
        ];
    }

    /**
     * Used to replace all owner tokens with emptiness so not to email out tokens.
     *
     * @return array
     */
    private function getEmptyTokens()
    {
        return [
            self::buildToken('email')      => '',
            self::buildToken('first_name') => '',
            self::buildToken('last_name')  => '',
        ];
    }

    /**
     * Used to replace all owner tokens with emptiness so not to email out tokens.
     *
     * @return array
     */
    private function getFakeTokens()
    {
        return [
            self::buildToken('email')      => '[Owner Email]',
            self::buildToken('first_name') => '[Owner First Name]',
            self::buildToken('last_name')  => '[Owner Last Name]',
        ];
    }

    /**
     * Creates a token using defined pattern.
     *
     * @param string $field
     *
     * @return string
     */
    private static function buildToken($field)
    {
        return sprintf(self::$ownerFieldSprintf, $field);
    }
}
