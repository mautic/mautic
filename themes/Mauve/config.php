<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

$config = array(
    "name"        => "Mauve",
    "features"    => array(
        "page",
        "email"
    ),
    "slots"       => array(
        "page" => array(
            "page_title",
            "top1_title",
            "top1",
            "top2_title",
            "top2",
            "top3_title",
            "top3",
            "portfolio1",
            "portfolio2",
            "portfolio3",
            "portfolio4",
            "portfolio5",
            "portfolio6",
            "section1_title",
            "section2_title",
            "section2",
            "section2_graphic",
            "cta",
            "cta_button",
            "footer"
        ),
        "email" => array(
            "body",
            "footer"
        )
    )
);

return $config;