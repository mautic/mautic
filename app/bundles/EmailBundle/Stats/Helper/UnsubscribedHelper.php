<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Stats\Helper;

use Mautic\EmailBundle\Stats\FetchOptions\EmailStatOptions;
use Mautic\LeadBundle\Entity\DoNotContact;
use Mautic\StatsBundle\Aggregate\Collection\StatCollection;

class UnsubscribedHelper extends AbstractHelper
{
    const NAME = 'email-unsubscribed';

    /**
     * @return string
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * @throws \Exception
     */
    public function generateStats(\DateTime $fromDateTime, \DateTime $toDateTime, EmailStatOptions $options, StatCollection $statCollection)
    {
        $query = $this->getQuery($fromDateTime, $toDateTime);
        $q     = $query->prepareTimeDataQuery('lead_donotcontact', 'date_added');

        $q->andWhere('t.channel = :channel')
            ->setParameter('channel', 'email')
            ->andWhere($q->expr()->eq('t.reason', ':reason'))
            ->setParameter('reason', DoNotContact::UNSUBSCRIBED);

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
