<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
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
            "left1",
            "left2",
            "left3",
            "right1",
            "right2",
            "right3",
            "top1",
            "top2",
            "top3",
            "main",
            "bottom1",
            "bottom2",
            "bottom3",
            "footer"
        ),
        "email" => array(
            "body",
            "footer"
        )
    )
);

return $config;