<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\EventListener;

use Mautic\LeadBundle\Entity\LeadRepository;
use Mautic\LeadBundle\Helper\PrimaryCompanyHelper;
use Mautic\LeadBundle\Helper\TokenHelper;
use Mautic\PageBundle\Event\UrlTokenReplaceEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final readonly class PageSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private LeadRepository $leadRepository,
        private PrimaryCompanyHelper $primaryCompanyHelper)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            UrlTokenReplaceEvent::class => ['onUrlTokenReplace', 0],
        ];
    }

    public function onUrlTokenReplace(UrlTokenReplaceEvent $event): void
    {
        if (!preg_match(TokenHelper::REGEX, $event->getContent())) {
            return;
        }

        $contact = $event->getLead();

        if (!$contact->getFields()) {
            $fields = $this->leadRepository->getFieldValues($contact->getId());
            $contact->setFields($fields);
        }

        $contactData = $this->primaryCompanyHelper->getProfileFieldsWithPrimaryCompany($contact);

        $event->setContent(TokenHelper::findLeadTokens($event->getContent(), $contactData, true));
    }
}
