<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
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

$collection->add('mautic_report_action', new Route('/reports/{objectAction}/{objectId}/{reportPage}',
    array(
        '_controller' => 'MauticReportBundle:Report:execute',
        'objectId'    => 0,
        'reportPage'  => 1
    ), array(
        'reportPage' => '\d+'
    )
));

return $collection;
