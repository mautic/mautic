<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

$config = array(
    "name"        => "Sunrise",
    "features"    => array(
        "page",
        "email"
    ),
    "slots"       => array(
        "page" => array(
            "header",
            "top1",
            "top2",
            "mid1",
            "mid2",
            "mid3",
            "footer"
        ),
        "email" => array(
            "body",
            "footer"
        )
    )
);

return $config;