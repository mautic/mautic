<?php

namespace Mautic\LeadBundle\EventListener;

use Mautic\EmailBundle\EmailEvents;
use Mautic\EmailBundle\Event\EmailBuilderEvent;
use Mautic\EmailBundle\Event\EmailSendEvent;
use Mautic\LeadBundle\Model\LeadModel;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

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

    public const onwerColumns = ['email', 'firstname', 'lastname', 'position', 'signature'];

    /**
     * OwnerSubscriber constructor.
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

    public function onEmailBuild(EmailBuilderEvent $event)
    {
        foreach (self::onwerColumns as $ownerAlias) {
            $event->addToken($this->buildToken($ownerAlias), $this->buildLabel($ownerAlias));
        }
    }

    public function onEmailDisplay(EmailSendEvent $event)
    {
        $this->onEmailGenerate($event);
    }

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
        $tokens          = [];
        $combinedContent = $event->getCombinedContent();
        foreach (self::onwerColumns as $ownerColumn) {
            $token = $this->buildToken($ownerColumn);
            if (false !== strpos($combinedContent, $token)) {
                $ownerColumnNormalized = str_replace(['firstname', 'lastname'], ['first_name', 'last_name'], $ownerColumn);
                $tokens[$token]        = $owner[$ownerColumnNormalized] ?? null;
            }
        }

        return $tokens;
    }

    /**
     * Used to replace all owner tokens with emptiness so not to email out tokens.
     *
     * @return array
     */
    private function getEmptyTokens()
    {
        $tokens = [];

        foreach (self::onwerColumns as $ownerColumn) {
            $tokens[$this->buildToken($ownerColumn)] = '';
        }

        return $tokens;
    }

    /**
     * Used to replace all owner tokens with emptiness so not to email out tokens.
     *
     * @return array
     */
    private function getFakeTokens()
    {
        $tokens = [];

        foreach (self::onwerColumns as $ownerColumn) {
            $tokens[$this->buildToken($ownerColumn)] = '['.$this->buildLabel($ownerColumn).']';
        }

        return $tokens;
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
