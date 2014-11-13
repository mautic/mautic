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

//tracking pixel
$collection->add('mautic_email_tracker', new Route('/p/email/{idHash}.gif',
    array(
        '_controller' => 'MauticEmailBundle:Public:trackingImage'
    )
));

//public webview of an email
$collection->add('mautic_email_webview', new Route('/p/email/view/{idHash}',
    array(
        '_controller' => 'MauticEmailBundle:Public:index'
    )
));

//unsubscribe URL
$collection->add('mautic_email_unsubscribe', new Route('/p/email/unsubscribe/{idHash}',
    array(
        '_controller' => 'MauticEmailBundle:Public:unsubscribe',
    )
));

//resubscribe URL
$collection->add('mautic_email_resubscribe', new Route('/p/email/resubscribe/{idHash}',
    array(
        '_controller' => 'MauticEmailBundle:Public:resubscribe',
    )
));

return $collection;