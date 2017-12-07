<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
if ($item->hasChildren() && $options['depth'] !== 0 && $item->getDisplayChildren()) {
    /* Top menu level start */
    if ($item->isRoot()) {
        echo '<ul class="nav mt-10" data-toggle="menu">'."\n";
    } else {
        echo "<ul{$view['menu']->parseAttributes($item->getChildrenAttributes())}>\n";
    }

    /* Submenu levels  start*/
    foreach ($item->getChildren() as $child) {
        if (!$child->isDisplayed()) {
            continue;
        }

        //builds the class attributes based on options
        $view['menu']->buildClasses($child, $matcher, $options);

        $showChildren = ($child->hasChildren() && $child->getDisplayChildren());
        $liAttributes = $child->getAttributes();
        $isAncestor   = $matcher->isAncestor($child, $options['matchingDepth']);

        $liAttributes['class'] = (isset($liAttributes['class'])) ? $liAttributes['class'].' nav-group' : 'nav-group';

        /* Menu item start */
        echo "<li{$view['menu']->parseAttributes($liAttributes)}>\n";

        $linkAttributes = $child->getLinkAttributes();
        $extras         = $child->getExtras();

        /* Menu link start */
        if ($showChildren) {
            //Main item
            echo '<a href="javascript:void(0);" data-target="#'.$linkAttributes['id'].'_child" data-toggle="submenu" data-parent=".nav" '.$view['menu']->parseAttributes($linkAttributes).">\n";
            echo '<span class="arrow pull-right text-right"></span>'."\n";
        } else {
            //Submenu item
            $url = $child->getUri();
            $url = (empty($url)) ? 'javascript:void(0);' : $url;
            if (empty($linkAttributes['target'])) {
                $linkAttributes['data-toggle'] = 'ajax';
            }
            echo "<a href=\"$url\"{$view['menu']->parseAttributes($linkAttributes)}>";
        }

        if (!empty($extras['iconClass'])) {
            echo "<span class=\"icon pull-left fa {$extras['iconClass']}\"></span>";
        }

        $labelAttributes = $child->getLabelAttributes();
        if (!isset($labelAttributes['class'])) {
            $labelAttributes['class'] = 'nav-item-name';
        }
        $labelPull = $extras['depth'] === 0 ? ' pull-left' : '';
        $labelAttributes['class'] .= ' text'.$labelPull;

        echo "<span{$view['menu']->parseAttributes($labelAttributes)}>{$view['translator']->trans($child->getLabel())}</span>";

        echo "</a>\n";
        /* Menu link end */

        /* Submenu items start */
        if ($showChildren) {
            $options['depth']         = ($options['depth']) ? $options['depth']-- : null;
            $options['matchingDepth'] = ($options['matchingDepth']) ? $options['matchingDepth']-- : null;

            $levelClass = $isAncestor ? 'nav-submenu collapse in' : 'nav-submenu collapse';

            //set the class
            $child->setChildrenAttribute('class', $levelClass);
            $child->setChildrenAttribute('id', $linkAttributes['id'].'_child');
            echo $view->render('MauticCoreBundle:Menu:main.html.php', [
                'item'    => $child,
                'options' => $options,
                'matcher' => $matcher,
            ]);
        }
        /* Submenu items end */

        /* Menu item end */
        echo "</li>\n";
    }
    /* Submenu items end */

    /* Top menu level end*/
    echo "</ul>\n";
}
