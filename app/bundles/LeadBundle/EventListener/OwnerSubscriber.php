<?php

namespace Mautic\LeadBundle\EventListener;

use Mautic\CoreBundle\Event\TokenReplacementEvent;
use Mautic\EmailBundle\EmailEvents;
use Mautic\EmailBundle\Event\EmailBuilderEvent;
use Mautic\EmailBundle\Event\EmailSendEvent;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\SmsBundle\Event\TokensBuildEvent;
use Mautic\SmsBundle\SmsEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class OwnerSubscriber implements EventSubscriberInterface
{
    private const OWNER_COLUMNS = ['email', 'firstname', 'lastname', 'position', 'signature'];

    private string $ownerFieldSprintf = '{ownerfield=%s}';

    private ?array $owners = null;

    public function __construct(
        private LeadModel $leadModel,
        private TranslatorInterface $translator,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            EmailEvents::EMAIL_ON_BUILD    => ['onEmailBuild', 0],
            EmailEvents::EMAIL_ON_SEND     => ['onEmailGenerate', 0],
            EmailEvents::EMAIL_ON_DISPLAY  => ['onEmailDisplay', 0],
            SmsEvents::ON_SMS_TOKENS_BUILD => ['onSmsTokensBuild', 0],
            SmsEvents::TOKEN_REPLACEMENT   => ['onSmsTokenReplacement', 0],
        ];
    }

    public function onEmailBuild(EmailBuilderEvent $event): void
    {
        $event->addTokens($this->getTokens());
    }

    public function onEmailDisplay(EmailSendEvent $event): void
    {
        $this->onEmailGenerate($event);
    }

    public function onEmailGenerate(EmailSendEvent $event): void
    {
        $event->addTokens($this->getGeneratedTokens($event));
    }

    public function onSmsTokensBuild(TokensBuildEvent $event): void
    {
        $tokens = array_merge($event->getTokens(), $this->getTokens());
        $event->setTokens($tokens);
    }

    public function onSmsTokenReplacement(TokenReplacementEvent $event): void
    {
        $contact             = $event->getLead()->getProfileFields();
        $contact['owner_id'] = $event->getLead()->getOwner() ? $event->getLead()->getOwner()->getId() : null;
        if (empty($contact['id']) && $event->getEntity()) {
            return;
        }
        $ownerTokens = $this->getOwnerTokens($contact, $event->getContent());
        $content     = str_replace(array_keys($ownerTokens), $ownerTokens, $event->getContent());
        $event->setContent($content);
    }

    /**
     * Generates an array of tokens based on the given token.
     *
     * * If contact[owner_id] === 0, then we need fake data
     * * If contact[owner_id] === null, then we should blank out tokens
     * * If contact[owner_id] > 0 AND User exists, then we should fill in tokens
     */
    private function getGeneratedTokens(EmailSendEvent $event): array
    {
        if ($event->isInternalSend()) {
            return $this->getFakeTokens();
        }

        $contact = $event->getLead();

        return $this->getOwnerTokens($contact, $event->getCombinedContent());
    }

    /**
     * Used to replace all owner tokens with emptiness so not to email out tokens.
     */
    private function getEmptyTokens(): array
    {
        $tokens = [];

        foreach (self::OWNER_COLUMNS as $ownerColumn) {
            $tokens[$this->buildToken($ownerColumn)] = '';
        }

        return $tokens;
    }

    /**
     * Used to replace all owner tokens with emptiness so not to email out tokens.
     */
    private function getFakeTokens(): array
    {
        $tokens = [];

        foreach (self::OWNER_COLUMNS as $ownerColumn) {
            $tokens[$this->buildToken($ownerColumn)] = '['.$this->buildLabel($ownerColumn).']';
        }

        return $tokens;
    }

    /**
     * Creates a token using defined pattern.
     *
     * @param string $field
     */
    private function buildToken($field): string
    {
        return sprintf($this->ownerFieldSprintf, $field);
    }

    /**
     * Creates translation ready label Owner Firstname etc.
     *
     * @param string $field
     */
    private function buildLabel($field): string
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

    /**
     * @param array<int|string> $contact
     *
     * @return array|string[]
     */
    private function getOwnerTokens($contact, string $content): array
    {
        if (empty($contact['owner_id'])) {
            return $this->getEmptyTokens();
        }

        $owner = $this->getOwner($contact['owner_id']);
        if (!$owner) {
            return $this->getEmptyTokens();
        }

        $tokens = [];
        foreach (self::OWNER_COLUMNS as $ownerColumn) {
            $token = $this->buildToken($ownerColumn);
            if (str_contains($content, $token)) {
                $ownerColumnNormalized = $this->getOwnerColumnNormalized($ownerColumn);
                $tokens[$token]        = $owner[$ownerColumnNormalized] ?? null;
            }
        }

        return $tokens;
    }

    /**
     * @return array<string>
     */
    private function getTokens(): array
    {
        $tokens = [];
        foreach (self::OWNER_COLUMNS as $ownerColumn) {
            $tokens[$this->buildToken($ownerColumn)] = $this->buildLabel($ownerColumn);
        }

        return $tokens;
    }

    /**
     * @return array|string|string[]
     */
    protected function getOwnerColumnNormalized(string $ownerColumn): string|array
    {
        return str_replace(['firstname', 'lastname'], ['first_name', 'last_name'], $ownerColumn);
    }
}
