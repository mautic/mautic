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

        // Generate data for Downloads line graph
        $unit = 'D';
        $downloadRepo = $this->factory->getEntityManager()->getRepository('MauticAssetBundle:Download');

        $data = $downloadRepo->prepareDownloadsGraphDataBefore(30, $unit);

        $queryBuilder = $this->factory->getEntityManager()->getConnection()->createQueryBuilder();
        $queryBuilder->from(MAUTIC_TABLE_PREFIX . 'asset_downloads', 'ad');
        $queryBuilder->leftJoin('ad', MAUTIC_TABLE_PREFIX . 'assets', 'a', 'a.id = ad.asset_id');
        $queryBuilder->select('ad.asset_id as asset, ad.date_download as dateDownload');
        $event->buildWhere($queryBuilder);
        $queryBuilder->andwhere($queryBuilder->expr()->gte('ad.date_download', ':date'))
            ->setParameter('date', $data['fromDate']->format('Y-m-d H:i:s'));
        $downloads = $queryBuilder->execute()->fetchAll();

        $timeStats = $downloadRepo->prepareDownloadsGraphDataAfter($data, $downloads, $unit);
        $timeStats['name'] = 'mautic.asset.graph.line.downloads';

        $event->setGraph('line', $timeStats);
    }
}
