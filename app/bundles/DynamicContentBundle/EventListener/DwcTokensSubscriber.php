<?php

declare(strict_types=1);

namespace Mautic\DynamicContentBundle\EventListener;

use Doctrine\DBAL\Connection;
use Mautic\CoreBundle\Event\ContentEvent;
use Mautic\CoreBundle\Event\EntityValidateEvent;
use Mautic\CoreBundle\Helper\BuilderTokenHelperFactory;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\DynamicContentBundle\DynamicContent\TypeList;
use Mautic\DynamicContentBundle\Helper\DynamicContentHelper;
use Mautic\EmailBundle\EmailEvents;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Event\EmailBuilderEvent;
use Mautic\EmailBundle\Model\EmailModel;
use Mautic\PageBundle\Entity\Page;
use Mautic\PageBundle\Model\PageModel;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final readonly class DwcTokensSubscriber implements EventSubscriberInterface
{
    public const DWCTOKENREGEX = '{dwc=(.*?)}Default content goes here{/dwc}';

    public const TOKEN_REGEX = '/{([^=}]+)(?:=([^}]+))?}/';

    public function __construct(
        private BuilderTokenHelperFactory $builderTokenHelperFactory,
        private Connection $connection,
        private EventDispatcherInterface $dispatcher,
        private DynamicContentHelper $dynamicContentHelper,
        private EmailModel $emailModel,
        private PageModel $pageModel,
        private CoreParametersHelper $coreParametersHelper,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            EmailEvents::EMAIL_ON_BUILD   => ['onEmailBuild', 0],
            EntityValidateEvent::class    => ['validateDWCTokensEligibility', 0],
        ];
    }

    public function onEmailBuild(EmailBuilderEvent $event): void
    {
        if ($event->tokensRequested([self::DWCTOKENREGEX])) {
            $dwcTokenHelper = $this->builderTokenHelperFactory->getBuilderTokenHelper(
                'dynamicContent',
                'dynamiccontent:dynamiccontents'
            );

            $expr = $this->connection->createExpressionBuilder()
                ->and('e.is_campaign_based <> 1 and e.slot_name is not null and e.type = "'.TypeList::TEXT.'"');

            $tokens = $dwcTokenHelper->getTokens(
                self::DWCTOKENREGEX,
                '',
                'slot_name',
                'slot_name',
                $expr
            );
            if (is_array($tokens)) {
                array_walk($tokens, function (string &$val): void {
                    $val = 'DWC:'.$val;
                });
            }

            $event->addTokens(is_array($tokens) ? $tokens : []);
        }
    }

    public function validateDWCTokensEligibility(EntityValidateEvent $event): void
    {
        if (!$this->coreParametersHelper->get('dynamic_content_use_token_eligibility_validation')) {
            return;
        }

        $entity = $event->getEntity();
        if ((!$entity instanceof Email && !$entity instanceof Page)
        || !$entity->getCustomHtml()) {
            return;
        }

        $contentEvent = new ContentEvent($entity->getCustomHtml());
        $this->dispatcher->dispatch($contentEvent);
        $content = $contentEvent->getContent();
        $model   = $event->getEntity() instanceof Email
            ? $this->emailModel
            : $this->pageModel;

        $allowedToken = array_keys($model->getBuilderComponents(null, ['tokens'])['tokens']);

        preg_match_all(DynamicContentHelper::DYNAMIC_WEB_CONTENT_REGEX, $content, $matches);
        $dwcSlotNames = array_unique(
            array_merge(
                $matches[1],
                $this->dynamicContentHelper->findDwcSlotNameFromContent($content)
            )
        );

        $dwcVariations = $this->dynamicContentHelper->getDwcsBySlotName($dwcSlotNames, true);
        foreach ($dwcVariations as $variation) {
            if (empty($variation->getContent())) {
                continue;
            }
            preg_match_all(self::TOKEN_REGEX, $variation->getContent(), $matches);
            $usedTokenNames = $matches[0];

            $invalidTokenNames   = array_diff($usedTokenNames, $allowedToken);
            if ($usedTokenNames !== [] && $invalidTokenNames !== []) {
                $event->getContext()->buildViolation('mautic.dynamicContent.error.token_disallowed', [
                    '%entity%'        => $event->getEntity() instanceof Email ? 'email' : 'page',
                    '%invalidTokens%' => implode(', ', $invalidTokenNames),
                    '%dwcId%'         => $variation->getId(),
                ])->addViolation();
            }
        }
    }
}
