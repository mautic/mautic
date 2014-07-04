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

$collection->add('mautic_form_results', new Route('/forms/results/{formId}/{page}',
    array(
        '_controller' => 'MauticFormBundle:Result:index',
        'page'        => 1,
        'formId'      => 0
    )
));

$collection->add('mautic_form_export', new Route('/forms/results/{formId}/export/{format}',
    array(
        '_controller' => 'MauticFormBundle:Result:export',
        'format'      => 'csv'
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

$collection->add('mautic_form_generateform', new Route('/p/form/generate.js',
    array('_controller' => 'MauticFormBundle:Public:generate')
));

return $collection;
