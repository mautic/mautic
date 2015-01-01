<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

$config = array(
    "name"        => "Mautic Bootstrap",
    "features"    => array(
        "page",
        "email"
    ),
    "slots"       => array(
        "page" => array(
            "page_title",
            "header",
            "section_title",
            "graphic_1",
            "graphic_1_title",
            "graphic_1_body",
            "graphic_2",
            "graphic_2_title",
            "graphic_2_body",
            "graphic_3",
            "graphic_3_title",
            "graphic_3_body",
            "graphic_4",
            "graphic_4_title",
            "graphic_4_body",
            "footer"
        ),
        "email" => array(
            "body",
            "footer"
        )
    )
);

return $config;