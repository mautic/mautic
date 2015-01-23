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
        "email",
        "form"
    ),
    "slots"       => array(
        "page" => array(
            "slideshow"         => array('type' => 'slideshow', 'placeholder' => 'mautic.page.builder.addcontent'),
            "page_title"        => array('type' => 'text', 'placeholder' => 'mautic.page.builder.addcontent'),
            "top1_title"        => array('type' => 'text', 'placeholder' => 'mautic.page.builder.addcontent'),
            "top1"              => array('type' => 'html', 'placeholder' => 'mautic.page.builder.addcontent'),
            "top2_title"        => array('type' => 'text', 'placeholder' => 'mautic.page.builder.addcontent'),
            "top2"              => array('type' => 'html', 'placeholder' => 'mautic.page.builder.addcontent'),
            "top3_title"        => array('type' => 'text', 'placeholder' => 'mautic.page.builder.addcontent'),
            "top3"              => array('type' => 'html', 'placeholder' => 'mautic.page.builder.addcontent'),
            "portfolio1"        => array('type' => 'html', 'placeholder' => 'mautic.page.builder.addcontent'),
            "portfolio2"        => array('type' => 'html', 'placeholder' => 'mautic.page.builder.addcontent'),
            "portfolio3"        => array('type' => 'html', 'placeholder' => 'mautic.page.builder.addcontent'),
            "portfolio4"        => array('type' => 'html', 'placeholder' => 'mautic.page.builder.addcontent'),
            "portfolio5"        => array('type' => 'html', 'placeholder' => 'mautic.page.builder.addcontent'),
            "portfolio6"        => array('type' => 'html', 'placeholder' => 'mautic.page.builder.addcontent'),
            "section1_title"    => array('type' => 'text', 'placeholder' => 'mautic.page.builder.addcontent'),
            "section2_title"    => array('type' => 'text', 'placeholder' => 'mautic.page.builder.addcontent'),
            "section2"          => array('type' => 'html', 'placeholder' => 'mautic.page.builder.addcontent'),
            "section2_graphic"  => array('type' => 'html', 'placeholder' => 'mautic.page.builder.addcontent'),
            "cta"               => array('type' => 'html', 'placeholder' => 'mautic.page.builder.addcontent'),
            "cta_button"        => array('type' => 'html', 'placeholder' => 'mautic.page.builder.addcontent'),
            "footer"            => array('type' => 'html', 'placeholder' => 'mautic.page.builder.addcontent')
        ),
        "email" => array(
            "header",
            "body",
            "footer"
        )
    )
);

return $config;