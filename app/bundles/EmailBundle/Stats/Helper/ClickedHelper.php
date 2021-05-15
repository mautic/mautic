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

class ClickedHelper extends AbstractHelper
{
    const NAME = 'email-clicked';

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
        $q     = $query->prepareTimeDataQuery('page_hits', 'date_hit', []);

        if ($segmentId = $options->getSegmentId()) {
            $q->innerJoin(
                't',
                '(SELECT DISTINCT email_id, lead_id FROM '.MAUTIC_TABLE_PREFIX.'email_stats WHERE list_id = :segmentId)',
                'es',
                't.source_id = es.email_id'
            );
            $q->setParameter('segmentId', $segmentId);
        }

        $q->andWhere('t.source = :source');
        $q->setParameter('source', 'email');

        $this->limitQueryToEmailIds($q, $options->getEmailIds(), 'source_id', 't');

        if (!$options->canViewOthers()) {
            $this->limitQueryToCreator($q);
        }

        $this->addCompanyFilter($q, $options->getCompanyId());
        $this->addCampaignFilterForEmailSource($q, $options->getCampaignId());
        $this->addSegmentFilter($q, $segmentId, 'es');

        $this->fetchAndBindToCollection($q, $statCollection);
    }
}
