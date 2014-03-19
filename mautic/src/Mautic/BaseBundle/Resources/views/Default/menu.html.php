<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
?>
<?php
//set root class
$listClass = ($item->isRoot()) ?
    $item->getChildrenAttribute("class") . " side-panel-nav" : $item->getChildrenAttribute("class");
$item->setChildrenAttribute("class", $listClass);
?>
<?php if ($item->hasChildren() && $options["depth"] !== 0 && $item->getDisplayChildren()): ?>
<ul<?php echo $parseAttributes($item->getChildrenAttributes()); ?>>
    <?php foreach ($item->getChildren() as $child): ?>
    <?php if (!$child->isDisplayed()) continue; ?>

    <?php $buildClasses($child, $matcher); //builds the class attributes based on options ?>
    <li<?php echo $parseAttributes($child->getAttributes()); ?>>

        <?php $icon = ($child->getExtra("icon")); ?>
        <?php if ($child->getUri() && (!$matcher->isCurrent($child) || $options["currentAsLink"])): //display as link ?>
        <a href="<?php echo $child->getUri(); ?>"<?php echo $parseAttributes($child->getLinkAttributes()); ?>>
            <span<?php echo $parseAttributes($child->getLabelAttributes()); ?>>
                <span class="glyphicon glyphicon-<?php echo $icon; ?>"></span><?php echo $child->getLabel();?>
            </span>
        </a>
        <?php else: //display as text ?>
        <span<?php echo $parseAttributes($child->getLabelAttributes()); ?>>
            <span class="glyphicon glyphicon-<?php echo $icon; ?>"></span><?php echo $child->getLabel();?>
        </span>
        <?php endif; ?>

        <?php
        //parse children/next level(s) through this same menu template
        if ($child->hasChildren() && $child->getDisplayChildren()):
        $options["depth"]         = ($options["depth"]) ? $options["depth"]-- : "";
        $options["matchingDepth"] = ($options["matchingDepth"]) ? $options["matchingDepth"]-- : "";

        //add on a level class
        $levelClass = $child->getChildrenAttribute("class") . " nav-level-" . $child->getLevel();
        $child->setChildrenAttribute("class", $levelClass);
        echo $view->render('MauticBaseBundle:Default:menu.html.php',
            array( "item"             => $child,
                   "options"          => $options,
                   "matcher"          => $matcher,
                   "parseAttributes"  => $parseAttributes,
                   "buildClasses"     => $buildClasses
            )
        );
        endif;
        ?>

    </li>

    <?php endforeach; ?>
</ul>
<?php endif; ?>