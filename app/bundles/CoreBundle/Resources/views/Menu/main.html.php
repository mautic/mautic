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
    <ul class="side-panel-nav margin-none padding-none" role="navigation">
    <?php else: ?>
    <ul<?php echo $view["menu_helper"]->parseAttributes($item->getChildrenAttributes()); //convert array to name="value" ?>>
    <?php endif; ?>
        <?php foreach ($item->getChildren() as $child):
        if (!$child->isDisplayed()) continue;
        $showChildren = ($child->hasChildren() && $child->getDisplayChildren());
        $view["menu_helper"]->buildClasses($child, $matcher, $options); //builds the class attributes based on options
        $liAttributes = $child->getAttributes();
        $isAncestor = $matcher->isAncestor($child, $options["matchingDepth"]);
        if ($isAncestor && !$showChildren): //make ancestor active if the current child is set to not be displayed
            $liAttributes['class'] = (isset($liAttributes['class'])) ? $liAttributes['class'] . " current" :
                "current";
        endif;
        ?>
        <li<?php echo $view["menu_helper"]->parseAttributes($liAttributes); ?>>
            <?php
            if ($showAsLink = ($child->getUri() && (!$matcher->isCurrent($child) || $options["currentAsLink"]))):
            $overrides = array();
            $linkAttributes = $child->getLinkAttributes();
            $extras         = $child->getExtras();
            if (!isset($linkAttributes['id']) && isset($extras['routeName']))
                $linkAttributes['id'] = $extras['routeName'];
            if (isset($linkAttributes['data-toggle']) && $linkAttributes['data-toggle'] == 'ajax'
                && !isset($linkAttributes['data-menu-link']) && isset($linkAttributes['id']))
                $linkAttributes['data-menu-link'] = $linkAttributes['id'];
            ?>
            <a href="<?php echo $child->getUri(); ?>"<?php echo $view["menu_helper"]->parseAttributes($linkAttributes); ?>>
            <?php endif; ?>

                <?php if ($icon = ($child->getExtra("iconClass"))): ?>
                <i class="fa fa-fw <?php echo $icon; ?>"></i>
                <?php endif; ?>
                <?php
                $labelAttributes = $child->getLabelAttributes();
                if (!isset($labelAttributes['class'])) $labelAttributes['class'] = 'nav-item-name';
                ?>
                <span<?php echo $view["menu_helper"]->parseAttributes($labelAttributes); ?>><?php
                    echo $view['translator']->trans($child->getLabel());?></span>
            <?php if ($showAsLink): ?>
            </a>
            <?php if ($showChildren): ?>
            <?php $onclick = (isset($linkAttributes['id'])) ?
                "onclick=\"Mautic.toggleSubMenu('#{$linkAttributes['id']}', event);\" " : ""; ?>

            <?php if ($isAncestor): ?>
            <span class="subnav-toggle"><i <?php echo $onclick; ?>class="fa fa-lg fa-toggle-down"></i></span>
            <?php else: ?>
            <span class="subnav-toggle"><i <?php echo $onclick; ?>class="fa fa-lg fa-toggle-left"></i></span>
            <?php endif; ?>
            <?php endif; ?>
            <?php endif; ?>

            <?php if ($showChildren): //parse children/next level(s)
                $options["depth"]         = ($options["depth"]) ? $options["depth"]-- : "";
                $options["matchingDepth"] = ($options["matchingDepth"]) ? $options["matchingDepth"]-- : "";

                //add on a level class
                $levelClass  = $child->getChildrenAttribute("class") . " nav-level nav-level-" . $child->getLevel();
                //note if the item has children
                if ($isAncestor):
                    $levelClass .= ($child->hasChildren()) ? " subnav-open" : "";
                else:
                    $levelClass .= ($child->hasChildren()) ? " subnav-closed" : "";
                endif;
                //set the class
                $child->setChildrenAttribute("class", $levelClass);
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