<?php

if (isset($customButtons)) {
    $view['buttons']->addButtons($customButtons);
}

//Function used to get identifier string for entity
$nameGetter = (!empty($nameGetter)) ? $nameGetter : 'getName';

//Dropdown direction
if (empty($pull)) {
    $pull = 'left';
}

//Custom query parameters for URLs
if (!isset($query)) {
    $query = [];
}

if (isset($tmpl)) {
    $query['tmpl'] = $tmpl;
}

//Edit mode for edit/actions (allows use of ajaxmodal)
if (!isset($editMode)) {
    $editMode = 'ajax';
}

if (!isset($editAttr)) {
    $editAttr = [];
}

//Template/common buttons
if (!isset($templateButtons)) {
    $templateButtons = [];
}

//Set langVar to routeBase if not set
if (!isset($translationBase)) {
    if (!isset($langVar)) {
        $langVar = (isset($routeBase)) ? $routeBase : '';
    }
    $translationBase = 'mautic.'.$langVar;
}

// Set index and action routes
if (isset($route) && !isset($actionRoute)) {
    $actionRoute = $route;
} elseif (!isset($actionRoute)) {
    $actionRoute = '';
    if (isset($routeBase)) {
        $actionRoute = 'mautic_'.str_replace('mautic_', '', $routeBase).'_action';
    }
}
if (!isset($indexRoute)) {
    $indexRoute = '';
    if (isset($routeBase)) {
        $indexRoute = 'mautic_'.str_replace('mautic_', '', $routeBase).'_index';
    }
}

if (!isset($routeVars)) {
    $routeVars = [];
}

//Extra HTML to be inserted after the buttons
if (!isset($extraHtml)) {
    $extraHtml = '';
}

//Wrapper such as li
if (!isset($wrapOpeningTag)) {
    $wrapOpeningTag = $wrapClosingTag = '';
}
$view['buttons']->setWrappingTags($wrapOpeningTag, $wrapClosingTag);

//Builder for custom buttons
$menuLink = (isset($menuLink)) ? " data-menu-link=\"{$menuLink}\"" : '';
$view['buttons']->setMenuLink($menuLink);

//Set a default button type (group or dropdown)
if (isset($groupType)) {
    $view['buttons']->setGroupType($groupType);
}

$buttonCount = $view['buttons']->getButtonCount();
