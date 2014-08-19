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

$collection->add('mautic_emailcategory_index', new Route('/emails/categories/{page}',
    array(
        '_controller' => 'MauticEmailBundle:Category:index',
        'page'        => 1,
    ), array(
        'page'    => '\d+'
    )
));

$collection->add('mautic_emailcategory_action', new Route('/emails/categories/{objectAction}/{objectId}',
    array(
        '_controller' => 'MauticEmailBundle:Category:execute',
        "objectId"    => 0
    )
));

$collection->add('mautic_email_index', new Route('/emails/{page}',
    array(
        '_controller' => 'MauticEmailBundle:Email:index',
        'page'        => 1,
    ), array(
        'page'    => '\d+'
    )
));

$collection->add('mautic_email_action', new Route('/emails/{objectAction}/{objectId}',
    array(
        '_controller' => 'MauticEmailBundle:Email:execute',
        "objectId"    => 0
    )
));