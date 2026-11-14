<?php

namespace Mautic\EmailBundle\Stats\Helper;

use Mautic\CoreBundle\Doctrine\DatabasePlatform;
use Mautic\EmailBundle\Stats\FetchOptions\EmailStatOptions;
use Mautic\StatsBundle\Aggregate\Collection\StatCollection;

final class ClickedHelper extends AbstractHelper
{
    public const NAME = 'email-clicked';

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

        // Platform-aware DISTINCT for COUNT
        $platform     = $query->getConnection()->getDatabasePlatform();
        $distinctExpr = DatabasePlatform::getDistinctMultiColumnExpression(
            $platform,
            't.email_id',
            't.redirect_id',
            't.lead_id'
        );

        $q     = $query->prepareTimeDataQuery('page_hits', 'date_hit', [], $distinctExpr);

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
