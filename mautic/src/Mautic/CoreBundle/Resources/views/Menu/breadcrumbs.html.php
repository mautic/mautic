<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

$lastCrumb = count($crumbs) - 1;
?>

<ol class="breadcrumb">
    <?php
    foreach ($crumbs as $crumbCount => $crumb):
        $id    = ($crumb["label"] == "root") ? "mautic_core_index" : $crumb["item"]->getLinkAttribute("id");
        $label = ($crumb["label"] == "root") ? "mautic.menu.core.index" : $crumb["label"];
        $route = ($crumb["label"] == "root") ? $view['router']->generate("mautic_core_index") : $crumb["uri"];
        ?>
        <li>
            <?php if ($lastCrumb === $crumbCount): ?>
                <?php echo $view['translator']->trans($label); ?>
            <?php else: ?>
                <a id="bc_<?php echo $id; ?>"
                   href="javascript: void(0);"
                   onclick="Mautic.loadMauticContent('<?php echo $route; ?>', '#<?php echo $id; ?>');">
                    <span><?php echo $view['translator']->trans($label); ?></span>
                </a>
            <?php endif; ?>
        </li>
    <?php endforeach; ?>
</ol>