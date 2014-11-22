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

$collection->add('mautic_category_index', new Route('/categories/{bundle}/{page}',
    array(
        '_controller' => 'MauticCategoryBundle:Category:index',
        'page'        => 1,
    ), array(
        'page' => '\d+'
    )
));

$collection->add('mautic_category_action', new Route('/categories/{bundle}/{objectAction}/{objectId}',
    array(
        '_controller' => 'MauticCategoryBundle:Category:executeCategory',
        'objectId'    => 0
    )
));

return $collection;
