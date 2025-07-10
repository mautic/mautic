<?php

namespace Mautic\EmailBundle\Stats\Helper;

use Mautic\EmailBundle\Stats\FetchOptions\EmailStatOptions;
use Mautic\LeadBundle\Entity\DoNotContact;
use Mautic\StatsBundle\Aggregate\Collection\StatCollection;

class BouncedHelper extends AbstractHelper
{
    public const NAME = 'email-bounced';

    public function getName(): string
    {
        return self::NAME;
    }

    /**
     * @throws \Exception
     */
    public function generateStats(\DateTime $fromDateTime, \DateTime $toDateTime, EmailStatOptions $options, StatCollection $statCollection): void
    {
        $query = $this->getQuery($fromDateTime, $toDateTime);
        $q     = $query->prepareTimeDataQuery('lead_donotcontact', 'date_added');

        $q->andWhere('t.channel = :channel')
            ->setParameter('channel', 'email')
            ->andWhere($q->expr()->eq('t.reason', ':reason'))
            ->setParameter('reason', DoNotContact::BOUNCED);

        $this->limitQueryToEmailIds($q, $options->getEmailIds(), 'channel_id', 't');

        $q->join('t', MAUTIC_TABLE_PREFIX.'email_stats', 'es', 't.channel_id = es.email_id AND t.channel = "email" AND t.lead_id = es.lead_id');

        if (true === $options->canViewOthers()) {
            $this->limitQueryToCreator($q, 'es.email_id');
        }
        $this->addCompanyFilter($q, $options->getCompanyId());
        $this->addCampaignFilter($q, $options->getCampaignId(), 'es');
        $this->addSegmentFilter($q, $options->getSegmentId(), 'es');

        $this->fetchAndBindToCollection($q, $statCollection);
    }
}
