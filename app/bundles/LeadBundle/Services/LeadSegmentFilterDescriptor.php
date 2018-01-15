<?php

/*
 * @copyright   2014-2018 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Services;

use Mautic\LeadBundle\Segment\FilterQueryBuilder\DncFilterQueryBuilder;
use Mautic\LeadBundle\Segment\FilterQueryBuilder\ForeignFuncFilterQueryBuilder;
use Mautic\LeadBundle\Segment\FilterQueryBuilder\ForeignValueFilterQueryBuilder;

class LeadSegmentFilterDescriptor extends \ArrayIterator
{
    private $translations;

    public function __construct()
    {
        $this->translations['lead_email_read_count'] = [
            'type'                => ForeignFuncFilterQueryBuilder::getServiceId(),
            'foreign_table'       => 'email_stats',
            'foreign_table_field' => 'lead_id',
            'table'               => 'leads',
            'table_field'         => 'id',
            'func'                => 'sum',
            'field'               => 'open_count',
        ];

        $this->translations['lead_email_read_date'] = [
            'type'                => ForeignValueFilterQueryBuilder::getServiceId(),
            'foreign_table'       => 'page_hits',
            'foreign_table_field' => 'lead_id',
            'table'               => 'leads',
            'table_field'         => 'id',
            'field'               => 'date_hit',
        ];

        $this->translations['dnc_bounced'] = [
            'type'                => DncFilterQueryBuilder::getServiceId(),
        ];

        $this->translations['dnc_bounced_sms'] = [
            'type'                => DncFilterQueryBuilder::getServiceId(),
        ];

        parent::__construct($this->translations);
    }
}

//case 'dnc_bounced':
//                case 'dnc_unsubscribed':
//                case 'dnc_bounced_sms':
//                case 'dnc_unsubscribed_sms':
//                    // Special handling of do not contact
//                    $func = (($func === 'eq' && $leadSegmentFilter->getFilter()) || ($func === 'neq' && !$leadSegmentFilter->getFilter())) ? 'EXISTS' : 'NOT EXISTS';
//
//                    $parts   = explode('_', $leadSegmentFilter->getField());
//                    $channel = 'email';
//
//                    if (count($parts) === 3) {
//                        $channel = $parts[2];
//                    }
//
//                    $channelParameter = $this->generateRandomParameterName();
//                    $subqb            = $this->entityManager->getConnection()->createQueryBuilder()
//                                                            ->select('null')
//                                                            ->from(MAUTIC_TABLE_PREFIX.'lead_donotcontact', $alias)
//                                                            ->where(
//                                                                $q->expr()->andX(
//                                                                    $q->expr()->eq($alias.'.reason', $exprParameter),
//                                                                    $q->expr()->eq($alias.'.lead_id', 'l.id'),
//                                                                    $q->expr()->eq($alias.'.channel', ":$channelParameter")
//                                                                )
//                                                            );
//
//                    $groupExpr->add(
//                        sprintf('%s (%s)', $func, $subqb->getSQL())
//                    );
//
//                    // Filter will always be true and differentiated via EXISTS/NOT EXISTS
//                    $leadSegmentFilter->setFilter(true);
//
//                    $ignoreAutoFilter = true;
//
//                    $parameters[$parameter]        = ($parts[1] === 'bounced') ? DoNotContact::BOUNCED : DoNotContact::UNSUBSCRIBED;
//                    $parameters[$channelParameter] = $channel;
//
//                    break;
