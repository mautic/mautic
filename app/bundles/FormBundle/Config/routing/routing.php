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

$collection->add('mautic_form_pagetoken_index', new Route('/forms/pagetokens/{page}',
    array(
        '_controller' => 'MauticFormBundle:SubscribedEvents\PageToken:index',
        'page'        => 1
    ),
    array(
        'page'    => '\d+'
    )
));

$collection->add('mautic_formaction_action', new Route('/forms/action/{objectAction}/{objectId}',
    array(
        '_controller' => 'MauticFormBundle:Action:execute',
        "objectId"    => 0
    )
));

$collection->add('mautic_formfield_action', new Route('/forms/field/{objectAction}/{objectId}',
    array(
        '_controller' => 'MauticFormBundle:Field:execute',
        "objectId"    => 0
    )
));

$collection->add('mautic_form_index', new Route('/forms/{page}',
    array(
        '_controller' => 'MauticFormBundle:Form:index',
        'page'        => 1,
    ), array(
        'page'    => '\d+'
    )
));

$collection->add('mautic_form_export', new Route('/forms/results/{objectId}/export/{format}',
    array(
        '_controller' => 'MauticFormBundle:Result:export',
        'format'      => 'csv'
    )
));

$collection->add('mautic_form_results', new Route('/forms/results/{objectId}/{page}',
    array(
        '_controller' => 'MauticFormBundle:Result:index',
        'page'        => 1,
        'objectId'    => 0
    )
));

$collection->add('mautic_form_action', new Route('/forms/{objectAction}/{objectId}',
    array(
        '_controller' => 'MauticFormBundle:Form:execute',
        "objectId"    => 0
    )
));

$collection->add('mautic_form_postresults', new Route('/p/form/submit',
    array('_controller' => 'MauticFormBundle:Public:submit')
));

$collection->add('mautic_form_preview', new Route('/p/form',
    array('_controller' => 'MauticFormBundle:Public:preview')
));

$collection->add('mautic_form_generateform', new Route('/p/form/generate.js',
    array('_controller' => 'MauticFormBundle:Public:generate')
));

$collection->add('mautic_form_postmessage', new Route('/p/form/message',
    array('_controller' => 'MauticFormBundle:Public:message')
));

return $collection;
