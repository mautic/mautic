<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

?>

<?php if ($item->hasChildren() && $options["depth"] !== 0 && $item->getDisplayChildren()): ?>
    <?php if ($item->isRoot()): ?>
        <ul class="nav mt-10" data-toggle="menu">
    <?php else: ?>
        <ul<?php echo $view["menu_helper"]->parseAttributes($item->getChildrenAttributes()); ?>>
    <?php endif; ?>
        <?php foreach ($item->getChildren() as $child):
            if (!$child->isDisplayed()) continue;

            //builds the class attributes based on options
            $view["menu_helper"]->buildClasses($child, $matcher, $options);

            $showChildren = ($child->hasChildren() && $child->getDisplayChildren());
            $liAttributes = $child->getAttributes();
            $isAncestor   = $matcher->isAncestor($child, $options["matchingDepth"]);
            //$showAsLink   = $child->getUri() && (!$matcher->isCurrent($child) || $options["currentAsLink"]);

            if ($isAncestor && !$showChildren): //make ancestor active if the current child is set to not be displayed
                $liAttributes['class'] = (isset($liAttributes['class'])) ? $liAttributes['class'] . " current" :
                    "current";
            endif;
        ?>

        <li class="nav-group" <?php echo $view["menu_helper"]->parseAttributes($liAttributes); ?> >

            <?php


            $linkAttributes = $child->getLinkAttributes();
            $extras         = $child->getExtras();

            if (!isset($linkAttributes['id']) && isset($extras['routeName'])):
                $linkAttributes['id'] = $extras['routeName'];
            endif;

            $onclick = (isset($linkAttributes['id'])) ? "onclick=\"Mautic.toggleSubMenu('#{$linkAttributes['id']}', event);\" " : "";

            if (isset($linkAttributes['data-toggle']) && $linkAttributes['data-toggle'] == 'ajax'
                && !isset($linkAttributes['data-menu-link']) && isset($linkAttributes['id'])):
                $linkAttributes['data-menu-link'] = $linkAttributes['id'];
            endif;
            ?>

            <?php if ($showChildren): ?>
                <a href="javascript:void(0);" data-target="#<?php echo $linkAttributes['id']; ?>_child" data-toggle="submenu" data-parent=".nav" <?php echo $view["menu_helper"]->parseAttributes($linkAttributes); ?>>
                <span class="arrow pull-right text-right"></span>
            <?php else: ?>
                <?php
                $url = $child->getUri();
                $url = (empty($url)) ? 'javascript:void(0);' : $url;
                ?>
                <a href="<?php echo $url; ?>"<?php echo $view["menu_helper"]->parseAttributes($linkAttributes); ?>>
            <?php endif; ?>

            <?php if ($icon = ($child->getExtra("iconClass"))): ?>
                <span class="icon fa <?php echo $icon; ?>"></span>
            <?php endif; ?>

                <?php
                $labelAttributes = $child->getLabelAttributes();
                if (!isset($labelAttributes['class'])):
                    $labelAttributes['class'] = 'nav-item-name';
                endif;
                $labelAttributes['class'] .= ' text';
                ?>

                <span <?php echo $view["menu_helper"]->parseAttributes($labelAttributes); ?> >
                    <?php echo $view['translator']->trans($child->getLabel());?>
                </span>

                <!--<span class="arrow"></span>-->
            </a>

            <?php
            //parse children/next level(s)
            if ($showChildren):
                $options["depth"]         = ($options["depth"]) ? $options["depth"]-- : "";
                $options["matchingDepth"] = ($options["matchingDepth"]) ? $options["matchingDepth"]-- : "";

                $levelClass = $isAncestor? "nav-submenu collapse in" : "nav-submenu collapse";

                //set the class
                $child->setChildrenAttribute("class", $levelClass);
                $child->setChildrenAttribute("id", $linkAttributes['id'] . '_child');
                echo $view->render('MauticCoreBundle:Menu:main.html.php',
                    array( "item"             => $child,
                           "options"          => $options,
                           "matcher"          => $matcher
                    )
                );
            endif;
            ?>
        </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>