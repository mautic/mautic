<?php

namespace Mautic\SmsBundle\Broadcast;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Mautic\CampaignBundle\Entity\ContactLimiterTrait;
use Mautic\CampaignBundle\Executioner\ContactFinder\Limiter\ContactLimiter;
use Mautic\ChannelBundle\Entity\MessageQueue;
use Mautic\SmsBundle\Entity\Sms;
use Mautic\SmsBundle\Entity\SmsRepository;

final class BroadcastQuery
{
    use ContactLimiterTrait;

    private QueryBuilder $query;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private readonly SmsRepository $smsRepository,
    ) {
    }

    public function getPendingContacts(Sms $sms, ContactLimiter $contactLimiter, ?int $batchLimit = null): array
    {
        if (null !== $batchLimit && $batchLimit <= 0) {
            return [];
        }

        $query = $this->getBasicQuery($sms);
        $query->select('l.id', 'MIN(ll.id) AS listId')
            ->groupBy('l.id');
        $this->updateQueryFromContactLimiter('lll', $query, $contactLimiter);

        if (null !== $batchLimit) {
            $query->setMaxResults($batchLimit);
        }

        return $query->executeQuery()->fetchAllAssociative();
    }

    /**
     * @param int[] $contactIds
     */
    public function getPendingContactsForContactIds(Sms $sms, array $contactIds): array
    {
        $contactIds = array_values(array_unique(array_map(intval(...), $contactIds)));
        if ([] === $contactIds) {
            return [];
        }

        return $this->getPendingContacts(
            $sms,
            new ContactLimiter(count($contactIds), contactIdList: $contactIds),
        );
    }

    public function getPendingCount(Sms $sms, ?ContactLimiter $contactLimiter = null): int
    {
        $query = $this->getBasicQuery($sms);
        $query->select('COUNT(DISTINCT l.id)')
            ->resetQueryPart('orderBy');

        if (null !== $contactLimiter) {
            $this->updateQueryFromContactLimiter('lll', $query, $contactLimiter, true);
        }

        return (int) $query->executeQuery()->fetchOne();
    }

    public function getBasicQuery(Sms $sms): QueryBuilder
    {
        $this->query = $this->smsRepository->getSegmentsContactsQuery($sms->getId());
        $this->query->andWhere(
            $this->query->expr()->or(
                $this->query->expr()->and(
                    $this->query->expr()->isNotNull('l.mobile'),
                    $this->query->expr()->neq('l.mobile', $this->query->expr()->literal(''))
                ),
                $this->query->expr()->and(
                    $this->query->expr()->isNotNull('l.phone'),
                    $this->query->expr()->neq('l.phone', $this->query->expr()->literal(''))
                )
            )
        );
        $this->excludeStatsRecords($this->getTranslationIds($sms));
        $this->excludeDnc();
        $this->excludeQueue();

        if (!$sms->isContinueSending() && null !== $sms->getPublishUp()) {
            $this->query->andWhere($this->query->expr()->lte('lll.date_added', ':max_date'))
                ->setParameter('max_date', $sms->getPublishUp(), Types::DATETIME_MUTABLE);
        }

        return $this->query;
    }

    /**
     * @param array<int, int|string> $smsIds
     */
    private function excludeStatsRecords(array $smsIds): void
    {
        $smsIds = array_values(array_unique(array_map(intval(...), $smsIds)));

        // Do not include leads that have already received text message
        $statQb = $this->entityManager->getConnection()->createQueryBuilder();
        $statQb->select('null')
            ->from(MAUTIC_TABLE_PREFIX.'sms_message_stats', 'stat')
            ->where(
                $statQb->expr()->and(
                    $statQb->expr()->eq('stat.lead_id', 'l.id'),
                    $statQb->expr()->in('stat.sms_id', ':relatedSmsIds')
                )
            );

        $this->query
            ->andWhere(sprintf('NOT EXISTS (%s)', $statQb->getSQL()))
            ->setParameter('relatedSmsIds', $smsIds, ArrayParameterType::INTEGER);
    }

    /**
     * @return int[]
     */
    private function getTranslationIds(Sms $sms): array
    {
        [$translationParent, $translationChildren] = $sms->getTranslations();
        $smsIds                                     = [];

        foreach (array_merge([$sms, $translationParent], $translationChildren) as $translation) {
            if ($translation instanceof Sms && $translation->getId()) {
                $smsIds[] = $translation->getId();
            }
        }

        return array_values(array_unique($smsIds));
    }

    private function excludeDnc(): void
    {
        // Do not include leads in the do not contact table
        $dncQb = $this->entityManager->getConnection()->createQueryBuilder();
        $dncQb->select('null')
            ->from(MAUTIC_TABLE_PREFIX.'lead_donotcontact', 'dnc')
            ->where(
                $dncQb->expr()->and(
                    $dncQb->expr()->eq('dnc.lead_id', 'l.id'),
                    $dncQb->expr()->eq('dnc.channel', $dncQb->expr()->literal('sms'))
                )
            );
        $this->query->andWhere(sprintf('NOT EXISTS (%s)', $dncQb->getSQL()));
    }

    private function excludeQueue(): void
    {
        // Do not include contacts where the message is pending in the message queue
        $mqQb = $this->entityManager->getConnection()->createQueryBuilder();
        $mqQb->select('null')
            ->from(MAUTIC_TABLE_PREFIX.'message_queue', 'mq')
            ->where(
                $mqQb->expr()->and(
                    $mqQb->expr()->eq('mq.lead_id', 'l.id'),
                    $mqQb->expr()->neq('mq.status', $mqQb->expr()->literal(MessageQueue::STATUS_SENT)),
                    $mqQb->expr()->eq('mq.channel', $mqQb->expr()->literal('sms'))
                )
            );
        $this->query->andWhere(sprintf('NOT EXISTS (%s)', $mqQb->getSQL()));
    }
}
