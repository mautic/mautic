<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

//load parameters array from local configuration
include_once "local.php";

foreach ($parameters as $k => $v) {
    $container->setParameter($k, $v);
}
