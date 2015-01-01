<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

$config = array(
    "name"        => "Trio: Three Column",
    "features"    => array(
        "page",
        "email"
    ),
    "slots"       => array(
        "page" => array(
            "top1",
            "top2",
            "cta",
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