<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\EventListener;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Mautic\LeadBundle\Entity\CompanyRepository;
use Mautic\LeadBundle\Entity\CompanySegment;
use Mautic\LeadBundle\Entity\SegmentCompany;
use Mautic\LeadBundle\Event\CompanyBuildSearchEvent;
use Mautic\LeadBundle\LeadEvents;
use Mautic\LeadBundle\Segment\Query\QueryBuilder;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class CompanySearchSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private TranslatorInterface $translator,
        private CompanyRepository $companyRepository,
        private Connection $connection,
    ) {
    }

    /**
     * @return array<string, string>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            LeadEvents::COMPANY_BUILD_SEARCH_COMMANDS => 'onBuildSearch',
        ];
    }

    public function onBuildSearch(CompanyBuildSearchEvent $event): void
    {
        $searchCommand = $this->translator->trans('mautic.company_segments.searchcommand.list');
        if ($event->getCommand() !== $searchCommand) {
            return;
        }

        $companySegmentIds = $this->getCompanySegmentIdsByAlias($event->getString());

        if ([] === $companySegmentIds) {
            return;
        }

        $uniqueParameterAlias = $event->getAlias();

        $sq = new QueryBuilder($this->connection);
        $sq->select('1')
            ->from(MAUTIC_TABLE_PREFIX.SegmentCompany::TABLE_NAME, SegmentCompany::TABLE_NAME)
            ->where(
                $sq->expr()->and(
                    $sq->expr()->eq(
                        $this->companyRepository->getTableAlias().'.id',
                        SegmentCompany::TABLE_NAME.'.company_id'
                    ),
                    $sq->expr()->in(SegmentCompany::TABLE_NAME.'.segment_id', ':'.$uniqueParameterAlias),
                    $sq->expr()->neq(SegmentCompany::TABLE_NAME.'.manually_removed', ':manually_removed_true')
                )
            );

        $parameters                           = $event->getParameters();
        $parameters['manually_removed_true']  = true;
        $event->setParameters($parameters);

        $event->setStrict(true);
        $event->setReturnParameters(false);

        if ($event->isNegation()) {
            $event->setSubQuery($sq->expr()->notExists($sq->getSQL()));
        } else {
            $event->setSubQuery($sq->expr()->exists($sq->getSQL()));
        }

        // Must use ArrayParameterType for IN clause
        $event->getQueryBuilder()->setParameter(
            $uniqueParameterAlias,
            $companySegmentIds,
            ArrayParameterType::INTEGER
        );

        $event->setSearchStatus(true);
    }

    /**
     * @return array<int>
     */
    private function getCompanySegmentIdsByAlias(string $segmentAlias): array
    {
        $result = (new QueryBuilder($this->connection))
            ->select('cs.id')
            ->from(MAUTIC_TABLE_PREFIX.CompanySegment::TABLE_NAME, 'cs')
            ->where('cs.alias = :alias')
            ->setParameter('alias', $segmentAlias)
            ->executeQuery()
            ->fetchFirstColumn();

        return array_map('intval', $result);
    }
}
