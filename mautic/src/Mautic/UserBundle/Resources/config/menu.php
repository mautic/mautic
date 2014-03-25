<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

$items = array(
    "mautic.menu.user.index" => array(
        "options" => array(
            "route"    => "mautic_user_index",
            "uri"      => "javascript: void(0)",
            "linkAttributes" => array(
                "onclick" =>
                    "toggleSubMenu(this);"
            ),
            "labelAttributes" => array(
                "class"   => "nav-item-name"
            ),
            "extras"=> array("icon" => "user")
        ),
        "children" => array(
            "mautic.menu.user.new" => array(
                "options" => array(
                    "route"    => "mautic_user_new",
                    "uri"      => "javascript: void(0)",
                    "linkAttributes" => array(
                        "onclick" =>
                            "loadMauticContent('" . $this->container->get("router")->generate("mautic_user_new") . "', this);"
                    ),
                    "labelAttributes" => array(
                        "class"   => "nav-item-name"
                    )
                )
            )
        )
    )
);