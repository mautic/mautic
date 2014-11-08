<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
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
        $metadataAsset = $this->factory->getEntityManager()->getClassMetadata('Mautic\\AssetBundle\\Entity\\Asset');
        $metadataDownload = $this->factory->getEntityManager()->getClassMetadata('Mautic\\AssetBundle\\Entity\\Download');
        $assetFields = $metadataAsset->getFieldNames();
        $downloadFields = $metadataDownload->getFieldNames();

        // Unset download id
        unset($downloadFields[0]);

        $columns  = array();

        foreach ($assetFields as $field) {
            $fieldData = $metadataAsset->getFieldMapping($field);
            $columns['a.' . $fieldData['columnName']] = array('label' => $field, 'type' => $fieldData['type']);
        }

        foreach ($downloadFields as $field) {
            $fieldData = $metadataDownload->getFieldMapping($field);
            $columns['ad.' . $fieldData['columnName']] = array('label' => $field, 'type' => $fieldData['type']);
        }

        $data = array(
            'display_name' => 'mautic.asset.asset.report.table',
            'columns'      => $columns
        );

        $event->addTable('assets', $data);
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
        // Context check, we only want to fire for Asset reports
        if ($event->getContext() != 'assets')
        {
            return;
        }

        $queryBuilder = $this->factory->getEntityManager()->getConnection()->createQueryBuilder();

        $queryBuilder->from(MAUTIC_TABLE_PREFIX . 'assets', 'a');
        $queryBuilder->leftJoin('a', MAUTIC_TABLE_PREFIX . 'asset_downloads', 'ad', 'a.id = ad.asset_id');

        $event->setQueryBuilder($queryBuilder);
    }

    /**
     * Initialize the QueryBuilder object to generate reports from
     *
     * @param ReportGeneratorEvent $event
     *
     * @return void
     */
    public function onReportGraphGenerate(ReportGraphEvent $event)
    {
        $report = $event->getReport();
        // Context check, we only want to fire for Asset reports
        if ($report->getSource() != 'assets')
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

            $data = GraphHelper::prepareLineGraphData($amount, $unit);

            $queryBuilder = $this->factory->getEntityManager()->getConnection()->createQueryBuilder();
            $queryBuilder->from(MAUTIC_TABLE_PREFIX . 'asset_downloads', 'ad');
            $queryBuilder->leftJoin('ad', MAUTIC_TABLE_PREFIX . 'assets', 'a', 'a.id = ad.asset_id');
            $queryBuilder->select('ad.asset_id as asset, ad.date_download as dateDownload');
            $event->buildWhere($queryBuilder);
            $queryBuilder->andwhere($queryBuilder->expr()->gte('ad.date_download', ':date'))
                ->setParameter('date', $data['fromDate']->format('Y-m-d H:i:s'));
            $downloads = $queryBuilder->execute()->fetchAll();

            $timeStats = GraphHelper::mergeLineGraphData($data, $downloads, $unit, 'dateDownload');
            $timeStats['name'] = 'mautic.asset.graph.line.downloads';

            $event->setGraph('line', $timeStats);
        }

        if (!$options || isset($options['graphName']) && $options['graphName'] == 'mautic.asset.table.most.downloaded') {
            $queryBuilder = $this->factory->getEntityManager()->getConnection()->createQueryBuilder();
            $event->buildWhere($queryBuilder);
            $limit = 10;
            $offset = 0;
            $items = $downloadRepo->getMostDownloaded($queryBuilder, $limit, $offset);
            $graphData = array();
            $graphData['data'] = $items;
            $graphData['name'] = 'mautic.asset.table.most.downloaded';
            $graphData['iconClass'] = 'fa-download';
            $graphData['link'] = 'mautic_asset_action';
            $event->setGraph('table', $graphData);
        }

        if (!$options || isset($options['graphName']) && $options['graphName'] == 'mautic.asset.table.top.referrers') {
            $queryBuilder = $this->factory->getEntityManager()->getConnection()->createQueryBuilder();
            $event->buildWhere($queryBuilder);
            $limit = 10;
            $offset = 0;
            $items = $downloadRepo->getTopReferrers($queryBuilder, $limit, $offset);
            $graphData = array();
            $graphData['data'] = $items;
            $graphData['name'] = 'mautic.asset.table.top.referrers';
            $graphData['iconClass'] = 'fa-download';
            $graphData['link'] = 'mautic_asset_action';
            $event->setGraph('table', $graphData);
        }

        if (!$options || isset($options['graphName']) && $options['graphName'] == 'mautic.asset.graph.pie.statuses') {
            $queryBuilder = $this->factory->getEntityManager()->getConnection()->createQueryBuilder();
            $event->buildWhere($queryBuilder);
            $items = $downloadRepo->getHttpStatuses($queryBuilder);
            $graphData = array();
            $graphData['data'] = $items;
            $graphData['name'] = 'mautic.asset.graph.pie.statuses';
            $graphData['iconClass'] = 'fa-globe';
            $event->setGraph('pie', $graphData);
        }
    }
}
