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
            if (!$child->isDisplayed()) continue; ?>
            <?php $view["menu_helper"]->buildClasses($child, $matcher, $options); //builds the class attributes based on options ?>
            <li<?php echo $view["menu_helper"]->parseAttributes($child->getAttributes()); ?>>

                <?php if ($showAsLink = ($child->getUri() && (!$matcher->isCurrent($child) || $options["currentAsLink"]))): ?>
                <a href="<?php echo $child->getUri(); ?>"<?php echo $view["menu_helper"]->parseAttributes($child->getLinkAttributes()); ?>>
                <?php endif; ?>

                    <?php if ($icon = ($child->getExtra("iconClass"))): ?>
                    <i class="fa <?php echo $icon; ?>"></i>
                    <?php endif; ?>

                    <span<?php echo $view["menu_helper"]->parseAttributes($child->getLabelAttributes()); ?>><?php
                        echo $view['translator']->trans($child->getLabel());?></span>

                    <?php if ($showChildren = ($child->hasChildren() && $child->getDisplayChildren())): ?>
                    <?php if ($isAncestor   = $matcher->isAncestor($child, $options["matchingDepth"])): ?>
                    <span class="subnav-toggle"><i class="fa fa-toggle-down"></i></span>
                    <?php else: ?>
                    <span class="subnav-toggle"><i class="fa fa-toggle-left"></i></span>
                    <?php endif; ?>
                    <?php endif; ?>

                <?php if ($showAsLink): ?>
                </a>
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