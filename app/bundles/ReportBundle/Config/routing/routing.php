<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\Route;

$collection = new RouteCollection();

$collection->add('mautic_report_index', new Route('/reports/{page}',
    array(
        '_controller' => 'MauticReportBundle:Report:index',
        'page'        => 1,
    ), array(
        'page' => '\d+'
    )
));

$collection->add('mautic_report_export', new Route('/reports/view/{objectId}/export/{format}',
    array(
        '_controller' => 'MauticReportBundle:Report:export',
        'format'      => 'csv'
    )
));

$collection->add('mautic_report_view', new Route('/reports/view/{objectId}/{reportPage}',
    array(
        '_controller' => 'MauticReportBundle:Report:view',
        'reportPage'  => 1
    ), array(
        'objectId'   => '\d+',
        'reportPage' => '\d+'
    )
));


$collection->add('mautic_report_action', new Route('/reports/{objectAction}/{objectId}',
    array(
        '_controller' => 'MauticReportBundle:Report:execute',
        'objectId'    => 0
    ), array(
        'objectId' => '\d+'
    )
));

return $collection;
