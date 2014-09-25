<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

use Symfony\Component\Routing\RouteCollection;

$collection = new RouteCollection();

//api docs has to be loaded by itself with a lower priority so that the trailing slash controller doesn't catch
$apiDocs = $loader->import('mautic.api_docs', 'mautic.api_docs');
$collection->addCollection($apiDocs);

return $collection;