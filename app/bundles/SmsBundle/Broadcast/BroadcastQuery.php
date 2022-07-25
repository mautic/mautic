<?php

namespace Mautic\SmsBundle\Broadcast;

use Doctrine\ORM\EntityManager;
use Mautic\CampaignBundle\Entity\ContactLimiterTrait;
use Mautic\CampaignBundle\Executioner\ContactFinder\Limiter\ContactLimiter;
use Mautic\ChannelBundle\Entity\MessageQueue;
use Mautic\SmsBundle\Entity\Sms;
use Mautic\SmsBundle\Model\SmsModel;

class BroadcastQuery
{
    use ContactLimiterTrait;

    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var SmsModel
     */
    private $smsModel;

    /**
     * @var \Doctrine\DBAL\Query\QueryBuilder
     */
    private $query;

    /**
     * BroadcastQuery constructor.
     */
    public function __construct(EntityManager $entityManager, SmsModel $smsModel)
    {
        $this->entityManager = $entityManager;
        $this->smsModel      = $smsModel;
    }

    /**
     * @return array
     */
    public function getPendingContacts(Sms $sms, ContactLimiter $contactLimiter)
    {
        $query = $this->getBasicQuery($sms);
        $query->select('DISTINCT l.id, ll.id as listId');
        $this->updateQueryFromContactLimiter('lll', $query, $contactLimiter);

        return $query->execute()->fetchAll();
    }

    /**
     * @return bool|string
     */
    public function getPendingCount(Sms $sms)
    {
        $query = $this->getBasicQuery($sms);
        $query->select('COUNT(DISTINCT l.id)');

        return $query->execute()->fetchColumn();
    }

    /**
     * @return \Doctrine\DBAL\Query\QueryBuilder
     */
    private function getBasicQuery(Sms $sms)
    {
        $this->query = $this->smsModel->getRepository()->getSegmentsContactsQuery($sms->getId());
        $this->query->andWhere(
            $this->query->expr()->orX(
                $this->query->expr()->orX(
                    $this->query->expr()->isNotNull('l.mobile'),
                    $this->query->expr()->neq('l.mobile', $this->query->expr()->literal(''))
                ),
                $this->query->expr()->orX(
                    $this->query->expr()->isNotNull('l.phone'),
                    $this->query->expr()->neq('l.phone', $this->query->expr()->literal(''))
                )
            )
        );
        $this->excludeStatsRecords($sms->getId());
        $this->excludeDnc();
        $this->excludeQueue();

        return $this->query;
    }

    private function excludeStatsRecords(int $smsId)
    {
        // Do not include leads that have already received text message
        $statQb = $this->entityManager->getConnection()->createQueryBuilder();
        $statQb->select('null')
            ->from(MAUTIC_TABLE_PREFIX.'sms_message_stats', 'stat')
            ->where(
                $statQb->expr()->andX(
                $statQb->expr()->eq('stat.lead_id', 'l.id'),
                $statQb->expr()->eq('stat.sms_id', $smsId)
                    )
            );

        $this->query->andWhere(sprintf('NOT EXISTS (%s)', $statQb->getSQL()));
    }

    private function excludeDnc()
    {
        // Do not include leads in the do not contact table
        $dncQb = $this->entityManager->getConnection()->createQueryBuilder();
        $dncQb->select('null')
            ->from(MAUTIC_TABLE_PREFIX.'lead_donotcontact', 'dnc')
            ->where(
                $dncQb->expr()->andX(
                    $dncQb->expr()->eq('dnc.lead_id', 'l.id'),
                    $dncQb->expr()->eq('dnc.channel', $dncQb->expr()->literal('sms'))
                )
            );
        $this->query->andWhere(sprintf('NOT EXISTS (%s)', $dncQb->getSQL()));
    }

    private function excludeQueue()
    {
        // Do not include contacts where the message is pending in the message queue
        $mqQb = $this->entityManager->getConnection()->createQueryBuilder();
        $mqQb->select('null')
            ->from(MAUTIC_TABLE_PREFIX.'message_queue', 'mq')
            ->where(
                $mqQb->expr()->andX(
                    $mqQb->expr()->eq('mq.lead_id', 'l.id'),
                    $mqQb->expr()->neq('mq.status', $mqQb->expr()->literal(MessageQueue::STATUS_SENT)),
                    $mqQb->expr()->eq('mq.channel', $mqQb->expr()->literal('sms'))
                )
            );
        $this->query->andWhere(sprintf('NOT EXISTS (%s)', $mqQb->getSQL()));
    }
}
