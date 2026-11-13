<?php

namespace Mautic\WebhookBundle\Entity;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Query\QueryBuilder as DbalQueryBuilder;
use Doctrine\ORM\QueryBuilder;
use Mautic\CoreBundle\Entity\CommonRepository;
use Mautic\EmailBundle\EmailEvents;
use Mautic\FormBundle\FormEvents;
use Mautic\LeadBundle\LeadEvents;
use Mautic\PageBundle\PageEvents;
use Mautic\SmsBundle\SmsEvents;

/**
 * @extends CommonRepository<Webhook>
 */
class WebhookRepository extends CommonRepository
{
    private const EVENT_COMMAND = 'mautic.webhook.searchcommand.event';

    /**
     * @param QueryBuilder|DbalQueryBuilder $q
     * @param object                        $filter
     *
     * @return array{0: mixed, 1: array<string, mixed>}
     */
    protected function addCatchAllWhereClause($q, $filter): array
    {
        return $this->addStandardCatchAllWhereClause($q, $filter, ['e.name']);
    }

    /**
     * @param QueryBuilder|DbalQueryBuilder $q
     * @param object                        $filter
     *
     * @return array{0: mixed, 1: array<string, mixed>}
     */
    protected function addSearchCommandWhereClause($q, $filter): array
    {
        if ($this->matchesCommand($filter->command, self::EVENT_COMMAND)) {
            return $this->buildEventSearchCommandExpression($q, $filter);
        }

        [$expr, $parameters] = parent::addSearchCommandWhereClause($q, $filter);

        if (false !== $expr) {
            return [$expr, $parameters];
        }

        $command = $filter->command;

        return match ($command) {
            $this->translator->trans('mautic.core.searchcommand.name'), $this->translator->trans('mautic.core.searchcommand.name', [], null, 'en_US') => $this->addStandardCatchAllWhereClause($q, $filter, [
                $this->getTableAlias().'.name',
            ]),
            default => $this->addStandardSearchCommandWhereClause($q, $filter),
        };
    }

    /**
     * @return string[]
     */
    public function getSearchCommands(): array
    {
        return array_merge(
            ['mautic.core.searchcommand.name', self::EVENT_COMMAND],
            $this->getStandardSearchCommands()
        );
    }

    /**
     * @return array<array<string>>
     */
    protected function getDefaultOrder(): array
    {
        return [
            [$this->getTableAlias().'.name', 'ASC'],
        ];
    }

    /**
     * @param QueryBuilder|DbalQueryBuilder $q
     *
     * @return array{0: mixed, 1: array<string, mixed>}
     */
    private function buildEventSearchCommandExpression($q, object $filter): array
    {
        $eventTypes = $this->resolveEventTypes($filter->string);
        if ([] === $eventTypes) {
            return $filter->not ? [false, []] : [$q->expr()->eq(1, 0), []];
        }

        $parameterName = $this->generateRandomParameterName();
        $aliasName = 'evt_sub_'.substr($parameterName, -8);

        if ($q instanceof QueryBuilder) {
            $subQuery = $this->_em->createQueryBuilder()
                ->select('1')
                ->from(Event::class, $aliasName)
                ->where($aliasName.'.webhook = '.$this->getTableAlias())
                ->andWhere($aliasName.'.eventType IN (:'.$parameterName.')');

            $expression = $q->expr()->exists($subQuery->getDQL());
            if ($filter->not) {
                $expression = $q->expr()->not($expression);
            }

            $q->setParameter($parameterName, $eventTypes, ArrayParameterType::STRING);

            return [$expression, []];
        }

        $eventTable = $this->_em->getClassMetadata(Event::class)->getTableName();
        $subQuery   = $this->_em->getConnection()->createQueryBuilder()
            ->select('1')
            ->from($eventTable, $aliasName)
            ->where($aliasName.'.webhook_id = '.$this->getTableAlias().'.id')
            ->andWhere($aliasName.'.event_type IN (:'.$parameterName.')');

        $expression = "EXISTS ({$subQuery->getSQL()})";
        if ($filter->not) {
            $expression = "NOT ({$expression})";
        }

        $q->setParameter($parameterName, $eventTypes, ArrayParameterType::STRING);

        return [$expression, []];
    }

    private function matchesCommand(string $command, string $translationKey): bool
    {
        return $command === $this->translator->trans($translationKey)
            || $command === $this->translator->trans($translationKey, [], null, 'en_US');
    }

    /**
     * @return string[]
     */
    private function resolveEventTypes(string $value): array
    {
        $aliases  = array_filter(array_map($this->normalizeEventAlias(...), preg_split('/\s*,\s*/', $value) ?: []));
        $aliasMap = $this->getEventAliasMap();

        $eventTypes = [];
        foreach ($aliases as $alias) {
            if (isset($aliasMap[$alias])) {
                $eventTypes = array_merge($eventTypes, $aliasMap[$alias]);

                continue;
            }

            if (str_contains($alias, '.')) {
                $eventTypes[] = $alias;
            }
        }

        return array_values(array_unique($eventTypes));
    }

    private function normalizeEventAlias(string $alias): string
    {
        $alias = strtolower(trim($alias));
        if ('' === $alias) {
            return '';
        }

        return preg_replace('/\s+/', '-', $alias) ?? $alias;
    }

    /**
     * @return array<string, string[]>
     */
    private function getEventAliasMap(): array
    {
        static $map;

        if (null !== $map) {
            return $map;
        }

        $map = [
            'company' => [
                LeadEvents::LEAD_COMPANY_CHANGE,
                LeadEvents::COMPANY_POST_SAVE,
                LeadEvents::COMPANY_POST_DELETE,
            ],
            'lead' => [
                LeadEvents::LEAD_POST_SAVE.'_new',
                LeadEvents::LEAD_POST_SAVE.'_update',
                LeadEvents::LEAD_POINTS_CHANGE,
                LeadEvents::LEAD_POST_DELETE,
                LeadEvents::CHANNEL_SUBSCRIPTION_CHANGED,
                LeadEvents::LEAD_LIST_CHANGE,
            ],
            'email' => [
                EmailEvents::EMAIL_ON_SEND,
                EmailEvents::EMAIL_ON_OPEN,
            ],
            'form' => [
                FormEvents::FORM_ON_SUBMIT,
            ],
            'page' => [
                PageEvents::PAGE_ON_HIT,
            ],
            'sms' => [
                SmsEvents::SMS_ON_SEND,
            ],
        ];

        $map['contact']        = $map['lead'];
        $map['contacts']       = $map['lead'];
        $map['leads']          = $map['lead'];
        $map['companies']      = $map['company'];
        $map['emailing']       = $map['email'];
        $map['emails']         = $map['email'];
        $map['forms']          = $map['form'];
        $map['pages']          = $map['page'];
        $map['text']           = $map['sms'];
        $map['texts']          = $map['sms'];
        $map['text-message']   = $map['sms'];
        $map['text_message']   = $map['sms'];
        $map['textmessages']   = $map['sms'];
        $map['text_messages']  = $map['sms'];
        $map['smses']          = $map['sms'];

        return $map;
    }
}
