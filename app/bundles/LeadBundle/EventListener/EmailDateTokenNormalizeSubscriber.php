<?php

namespace Mautic\LeadBundle\EventListener;

use Mautic\CoreBundle\Helper\DateTime\DateTimeLocalization;
use Mautic\EmailBundle\EmailEvents;
use Mautic\EmailBundle\Event\EmailSendEvent;
use Mautic\LeadBundle\Helper\TokenHelper;
use Mautic\LeadBundle\Model\CompanyModel;
use Mautic\LeadBundle\Model\LeadModel;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class EmailDateTokenNormalizeSubscriber implements EventSubscriberInterface
{
    private CompanyModel $companyModel;

    private DateTimeLocalization $dateTimeLocalization;

    private LeadModel $leadModel;

    public function __construct(LeadModel $leadModel, CompanyModel $companyModel, DateTimeLocalization $dateTimeLocalization)
    {
        $this->leadModel            = $leadModel;
        $this->companyModel         = $companyModel;
        $this->dateTimeLocalization = $dateTimeLocalization;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            EmailEvents::EMAIL_ON_SEND => ['onEmailGenerate', -10],
        ];
    }

    public function onEmailGenerate(EmailSendEvent $event)
    {
        $fields              = $this->getFields();
        $existedTokens       = $event->getTokens(false);
        $content             = $event->getContent();
        $foundMatches        = preg_match_all(TokenHelper::REGEX, $content, $matches);
        $contactLocale       = is_array($event->getLead()) ? $event->getLead()['preferred_locale'] : null;

        if ($foundMatches) {
            foreach ($matches[2] as $key => $match) {
                if (false !== strpos($match, '%7C')) {
                    $match = urldecode($match);
                }
                if ($this->isContactDateToken($fields, TokenHelper::getFieldAlias($match))) {
                    $token = $matches[0][$key];
                    if (isset($existedTokens[$token])) {
                        $event->addToken(
                            $token,
                            $this->dateTimeLocalization->localize($existedTokens[$token], $contactLocale)
                        );
                    }
                }
            }
        }
    }

    protected function getFields(): array
    {
        return array_merge(
            $this->leadModel->getRepository()->getCustomFieldList('lead')[0],
            $this->companyModel->getRepository()->getCustomFieldList('company')[0]
        );
    }

    protected function isContactDateToken(array $fields, string $alias): bool
    {
        return isset($fields[$alias]) && in_array($fields[$alias]['type'], ['date', 'datetime']);
    }
}
