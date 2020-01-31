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

use Mautic\CoreBundle\Helper\ArrayHelper;
use Mautic\EmailBundle\EmailEvents;
use Mautic\EmailBundle\Event\EmailBuilderEvent;
use Mautic\EmailBundle\Event\EmailSendEvent;
use Mautic\LeadBundle\Model\LeadModel;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Translation\TranslatorInterface;

class OwnerSubscriber implements EventSubscriberInterface
{
    /**
     * @var string
     */
    private $ownerFieldSprintf = '{ownerfield=%s}';

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var LeadModel
     */
    private $leadModel;

    /**
     * @var array
     */
    private $owners;

    /**
     * OwnerSubscriber constructor.
     *
     * @param LeadModel           $leadModel
     * @param TranslatorInterface $translator
     */
    public function __construct(LeadModel $leadModel, TranslatorInterface $translator)
    {
        $this->translator = $translator;
        $this->leadModel  = $leadModel;
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
        $event->addToken($this->buildToken('email'), $this->buildLabel('email'));
        $event->addToken($this->buildToken('first_name'), $this->buildLabel('firstname'));
        $event->addToken($this->buildToken('last_name'), $this->buildLabel('lastname'));
        $event->addToken($this->buildToken('position'), $this->buildLabel('position'));
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
        $event->addTokens($this->getGeneratedTokens($event));
    }

    /**
     * Generates an array of tokens based on the given token.
     *
     * * If contact[owner_id] === 0, then we need fake data
     * * If contact[owner_id] === null, then we should blank out tokens
     * * If contact[owner_id] > 0 AND User exists, then we should fill in tokens
     *
     * @param EmailSendEvent $event
     *
     * @return array
     */
    private function getGeneratedTokens(EmailSendEvent $event)
    {
        $contact = $event->getLead();

        if ($event->isInternalSend()) {
            return $this->getFakeTokens();
        }

        if (empty($contact['owner_id'])) {
            return $this->getEmptyTokens();
        }

        $owner = $this->getOwner($contact['owner_id']);

        if (!$owner) {
            return $this->getEmptyTokens();
        }

        return [
            $this->buildToken('email')      => ArrayHelper::getValue('email', $owner),
            $this->buildToken('first_name') => ArrayHelper::getValue('first_name', $owner),
            $this->buildToken('last_name')  => ArrayHelper::getValue('last_name', $owner),
            $this->buildToken('position')   => ArrayHelper::getValue('position', $owner),
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
            $this->buildToken('email')      => '',
            $this->buildToken('first_name') => '',
            $this->buildToken('last_name')  => '',
            $this->buildToken('position')   => '',
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
            $this->buildToken('email')      => '['.$this->buildLabel('email').']',
            $this->buildToken('first_name') => '['.$this->buildLabel('firstname').']',
            $this->buildToken('last_name')  => '['.$this->buildLabel('lastname').']',
            $this->buildToken('position')   => '['.$this->buildLabel('position').']',
        ];
    }

    /**
     * Creates a token using defined pattern.
     *
     * @param string $field
     *
     * @return string
     */
    private function buildToken($field)
    {
        return sprintf($this->ownerFieldSprintf, $field);
    }

    /**
     * Creates translation ready label Owner Firstname etc.
     *
     * @param string $field
     *
     * @return string
     */
    private function buildLabel($field)
    {
        return sprintf(
            '%s %s',
            $this->translator->trans('mautic.lead.list.filter.owner'),
            $this->translator->trans('mautic.core.'.$field)
        );
    }

    /**
     * @param $ownerId
     *
     * @return array|null
     */
    private function getOwner($ownerId)
    {
        if (!isset($this->owners[$ownerId])) {
            $this->owners[$ownerId] = $this->leadModel->getRepository()->getLeadOwner($ownerId);
        }

        return $this->owners[$ownerId];
    }
}
