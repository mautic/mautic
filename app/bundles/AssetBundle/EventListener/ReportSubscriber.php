<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\AssetBundle\EventListener;

use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\CoreBundle\Helper\GraphHelper;
use Mautic\ReportBundle\Event\ReportBuilderEvent;
use Mautic\ReportBundle\Event\ReportGeneratorEvent;
use Mautic\ReportBundle\Event\ReportGraphEvent;
use Mautic\ReportBundle\ReportEvents;

/**
 * Class ReportSubscriber
 *
 * @package Mautic\AssetBundle\EventListener
 */
class ReportSubscriber extends CommonSubscriber
{

    /**
     * @return array
     */
    static public function getSubscribedEvents()
    {
        return array(
            ReportEvents::REPORT_ON_BUILD    => array('onReportBuilder', 0),
            ReportEvents::REPORT_ON_GENERATE => array('onReportGenerate', 0),
            ReportEvents::REPORT_ON_GRAPH_GENERATE => array('onReportGraphGenerate', 0)
        );
    }

    /**
     * Add available tables and columns to the report builder lookup
     *
     * @param ReportBuilderEvent $event
     *
     * @return void
     */
    public function onReportBuilder(ReportBuilderEvent $event)
    {
        if ($event->checkContext(array('assets', 'asset.downloads'))) {
            // Assets
            $prefix  = 'a.';
            $columns = array(
                $prefix . 'download_count'        => array(
                    'label' => 'mautic.asset.report.download_count',
                    'type'  => 'int'
                ),
                $prefix . 'unique_download_count' => array(
                    'label' => 'mautic.asset.report.unique_download_count',
                    'type'  => 'int'
                ),
                $prefix . 'alias'                 => array(
                    'label' => 'mautic.report.field.alias',
                    'type'  => 'string'
                ),
                $prefix . 'lang'                  => array(
                    'label' => 'mautic.report.field.lang',
                    'type'  => 'string'
                ),
                $prefix . 'title'                 => array(
                    'label' => 'mautic.asset.report.title',
                    'type'  => 'string'
                )
            );

            $columns = array_merge($columns, $event->getStandardColumns($prefix, array('name')), $event->getCategoryColumns());
            $event->addTable('assets', array(
                'display_name' => 'mautic.asset.report.table',
                'columns'      => $columns
            ));

            if ($event->checkContext(array('asset.downloads'))) {
                // Downloads
                $downloadPrefix  = 'ad.';
                $downloadColumns = array(
                    $downloadPrefix . 'date_download' => array(
                        'label' => 'mautic.asset.report.download.date_download',
                        'type'  => 'datetime'
                    ),
                    $downloadPrefix . 'code'          => array(
                        'label' => 'mautic.asset.report.download.code',
                        'type'  => 'string'
                    ),
                    $downloadPrefix . 'referer'       => array(
                        'label' => 'mautic.asset.report.download.referer',
                        'type'  => 'string'
                    ),
                    $downloadPrefix . 'source'        => array(
                        'label' => 'mautic.report.field.source',
                        'type'  => 'string'
                    ),
                    $downloadPrefix . 'source_id'     => array(
                        'label' => 'mautic.report.field.source_id',
                        'type'  => 'int'
                    )
                );

                $event->addTable('asset.downloads', array(
                    'display_name' => 'mautic.asset.report.downloads.table',
                    'columns'      => array_merge($columns, $downloadColumns, $event->getLeadColumns(), $event->getIpColumn())
                ));
            }
        }
    }

    /**
     * Initialize the QueryBuilder object to generate reports from
     *
     * @param ReportGeneratorEvent $event
     *
     * @return void
     */
    public function onReportGenerate(ReportGeneratorEvent $event)
    {
        $context = $event->getContext();
        if ($context == 'assets') {
            $qb = $this->factory->getEntityManager()->getConnection()->createQueryBuilder();

            $qb->from(MAUTIC_TABLE_PREFIX . 'assets', 'a');
            $event->addCategoryLeftJoin($qb, 'a');

            $event->setQueryBuilder($qb);
        } elseif ($context == 'asset.downloads') {
            $qb = $this->factory->getEntityManager()->getConnection()->createQueryBuilder();

            $qb->from(MAUTIC_TABLE_PREFIX . 'asset_downloads', 'ad')
                ->leftJoin('ad', MAUTIC_TABLE_PREFIX . 'assets', 'a', 'a.id = ad.asset_id');
            $event->addCategoryLeftJoin($qb, 'a');
            $event->addLeadLeftJoin($qb, 'ad');
            $event->addIpAddressLeftJoin($qb, 'ad');

            $event->setQueryBuilder($qb);
        }
    }

    /**
     * Initialize the QueryBuilder object to generate reports from
     *
     * @param ReportGraphEvent $event
     *
     * @return void
     */
    public function onReportGraphGenerate(ReportGraphEvent $event)
    {
        $report = $event->getReport();
        // Context check, we only want to fire for Asset reports
        if ($report->getSource() != 'asset.downloads')
        {
            return;
        }

        $options = $event->getOptions();
        $downloadRepo = $this->factory->getEntityManager()->getRepository('MauticAssetBundle:Download');

        if (!$options || isset($options['graphName']) && $options['graphName'] == 'mautic.asset.graph.line.downloads') {
            // Generate data for Downloads line graph
            $unit = 'D';
            $amount = 30;

            if (isset($options['amount'])) {
                $amount = $options['amount'];
            }

            if (isset($options['unit'])) {
                $unit = $options['unit'];
            }

            $data = GraphHelper::prepareDatetimeLineGraphData($amount, $unit, array('downloaded'));

            $qb = $this->factory->getEntityManager()->getConnection()->createQueryBuilder();
            $qb->from(MAUTIC_TABLE_PREFIX . 'asset_downloads', 'ad');
            $qb->leftJoin('ad', MAUTIC_TABLE_PREFIX . 'assets', 'a', 'a.id = ad.asset_id');
            $qb->select('ad.asset_id as asset, ad.date_download as dateDownload');
            $event->buildWhere($qb);
            $qb->andwhere($qb->expr()->gte('ad.date_download', ':date'))
                ->setParameter('date', $data['fromDate']->format('Y-m-d H:i:s'));
            $downloads = $qb->execute()->fetchAll();

            $timeStats = GraphHelper::mergeLineGraphData($data, $downloads, $unit, 0, 'dateDownload');
            $timeStats['name'] = 'mautic.asset.graph.line.downloads';

            $event->setGraph('line', $timeStats);
        }

        if (!$options || isset($options['graphName']) && $options['graphName'] == 'mautic.asset.table.most.downloaded') {
            $qb = $this->factory->getEntityManager()->getConnection()->createQueryBuilder();
            $event->buildWhere($qb);
            $limit = 10;
            $offset = 0;
            $items = $downloadRepo->getMostDownloaded($qb, $limit, $offset);
            $graphData = array();
            $graphData['data'] = $items;
            $graphData['name'] = 'mautic.asset.table.most.downloaded';
            $graphData['iconClass'] = 'fa-download';
            $graphData['link'] = 'mautic_asset_action';
            $event->setGraph('table', $graphData);
        }

        if (!$options || isset($options['graphName']) && $options['graphName'] == 'mautic.asset.table.top.referrers') {
            $qb = $this->factory->getEntityManager()->getConnection()->createQueryBuilder();
            $event->buildWhere($qb);
            $limit = 10;
            $offset = 0;
            $items = $downloadRepo->getTopReferrers($qb, $limit, $offset);
            $graphData = array();
            $graphData['data'] = $items;
            $graphData['name'] = 'mautic.asset.table.top.referrers';
            $graphData['iconClass'] = 'fa-download';
            $graphData['link'] = 'mautic_asset_action';
            $event->setGraph('table', $graphData);
        }

        if (!$options || isset($options['graphName']) && $options['graphName'] == 'mautic.asset.graph.pie.statuses') {
            $qb = $this->factory->getEntityManager()->getConnection()->createQueryBuilder();
            $event->buildWhere($qb);
            $items = $downloadRepo->getHttpStatuses($qb);
            $graphData = array();
            $graphData['data'] = $items;
            $graphData['name'] = 'mautic.asset.graph.pie.statuses';
            $graphData['iconClass'] = 'fa-globe';
            $event->setGraph('pie', $graphData);
        }
    }
}
