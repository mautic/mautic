<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

//Set vars commonly used
if (!isset($buttonCount)) {
    $buttonCount = 0;
}

//Function used to get identifier string for entity
$nameGetter = (!empty($nameGetter)) ? $nameGetter : 'getName';

//Dropdown direction
if (empty($pull)) {
    $pull = 'left';
}

//Custom query parameters for URLs
if (!isset($query)) {
    $query = array();
}

//Edit mode for edit/actions (allows use of ajaxmodal)
if (!isset($editMode)) {
    $editMode = "ajax";
}

if (!isset($editAttr)) {
    $editAttr = '';
} elseif (is_array($editAttr)) {
    $string = "";
    foreach ($editAttr as $attr => $val) {
        $string .= " $attr=\"$val\"";
    }
    $editAttr = $string;
} else {
    $editAttr = " $editAttr";
}

//Template/common buttons
if (!isset($templateButtons)) {
    $templateButtons = array();
}

//Set langVar to routeBase if not set
if (!isset($langVar) && isset($routeBase)) {
    $langVar = $routeBase;
}

//Set a default button type (group or dropdown)
if (!isset($groupType)) {
    $groupType = 'group';
}

//Extra HTML to be inserted after the buttons
if (!isset($extraHtml)) {
    $extraHtml = '';
}

if (!isset($wrapOpeningTag)) {
    $wrapOpeningTag = $wrapClosingTag = '';
}

//Builder for custom buttons
$menuLink  = (isset($menuLink)) ? " data-menu-link=\"{$menuLink}\"" : '';
$buildCustom = function($c, $buttonCount) use ($menuLink, $view, $wrapOpeningTag, $wrapClosingTag, $groupType) {
    $buttons = '';

    //Wrap links in a tag
    if ($groupType == 'dropdown' && $buttonCount > 0) {
        $wrapOpeningTag = "<li>\n";
        $wrapClosingTag = "</li>\n";
    }

    if (isset($c['confirm'])) {
        if ($groupType == 'dropdown' && !isset($c['confirm']['btnClass'])) {
            $c['confirm']['btnClass'] = "";
        }
        $buttons .= $wrapOpeningTag.$view->render('MauticCoreBundle:Helper:confirm.html.php', $c['confirm'])."$wrapClosingTag\n";
    } else {
        $attr = $menuLink;

        if (!isset($c['attr'])) {
            $c['attr'] = array();
        }

        if(($groupType == 'group' || ($groupType == 'dropdown' && $buttonCount === 0)) && !isset($c['attr']['class'])) {
            $c['attr']['class'] = 'btn btn-default';
        }

        if (!isset($c['attr']['data-toggle'])) {
            $c['attr']['data-toggle'] = 'ajax';
        }

        foreach ($c['attr'] as $k => $v):
            $attr .= " $k=" . '"' . $v . '"';
        endforeach;

        $buttonContent  = (isset($c['iconClass'])) ? '<i class="' . $c['iconClass'] . '"></i> ' : '';
        $buttonContent .= $view['translator']->trans($c['btnText']);
        $buttons       .= "$wrapOpeningTag<a{$attr}>{$buttonContent}</a>$wrapClosingTag\n";
    }

    return $buttons;
};

//Build pre template custom buttons
if (!isset($preCustomButtons)) {
    $preCustomButtons = array();
}
$renderPreCustomButtons = function(&$buttonCount, $dropdownHtml = '') use ($preCustomButtons, $groupType, $buildCustom) {
    $preCustomButtonContent = '';

    foreach ($preCustomButtons as $c) {
        if ($groupType == 'dropdown' && $buttonCount === 1) {
            $preCustomButtonContent .= $dropdownHtml;
        }
        $preCustomButtonContent .= $buildCustom($c, $buttonCount);
        $buttonCount++;
    }

    return $preCustomButtonContent;
};

//Build post template custom buttons
if (isset($customButtons)) {
    $postCustomButtons = $customButtons;
} elseif (!isset($postCustomButtons)) {
    $postCustomButtons = array();
}
$renderPostCustomButtons = function(&$buttonCount, $dropdownHtml = '') use ($postCustomButtons, $groupType, $buildCustom) {
    $postCustomButtonContent = '';

    if (!empty($postCustomButtons)) {
        foreach ($postCustomButtons as $c) {
            if ($groupType == 'dropdown' && $buttonCount === 1) {
                $postCustomButtonContent .= $dropdownHtml;
            }
            $postCustomButtonContent .= $buildCustom($c, $buttonCount);
            $buttonCount++;
        }
    }

    return $postCustomButtonContent;
};