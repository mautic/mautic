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
use Mautic\StatsBundle\Aggregate\Collection\StatCollection;

class SentHelper extends AbstractHelper
{
    const NAME = 'email-sent';

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
        $column = $this->generatedColumnsProvider->generatedColumnsAreSupported() ? 'generated_sent_date' : 'date_sent';
        $query  = $this->getQuery($fromDateTime, $toDateTime);
        $q      = $query->prepareTimeDataQuery('email_stats', $column, $options->getFilters());

        $this->limitQueryToEmailIds($q, $options->getEmailIds(), 'email_id', 't');

        if (!$options->canViewOthers()) {
            $this->limitQueryToCreator($q);
        }

        $this->addCompanyFilter($q, $options->getCompanyId());
        $this->addCampaignFilter($q, $options->getCampaignId());
        $this->addSegmentFilter($q, $options->getSegmentId());

        $this->fetchAndBindToCollection($q, $statCollection);
    }
}
