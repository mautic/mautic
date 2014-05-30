<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

$defaultOrder = (!empty($default)) ? $orderBy : "";
$order        = $app->getSession()->get("mautic.{$sessionVar}.orderby", $defaultOrder);
$dir          = $app->getSession()->get("mautic.{$sessionVar}.orderbydir", "ASC");
$target       = (!empty($target)) ? $target : '.main-panel-content-wrapper';
$tmpl         = (!empty($tmpl)) ? $tmpl : 'content';
?>
<th<?php echo (!empty($class)) ? ' class="' . $class . '"': ""; ?>>
    <a href="javascript: void(0);" onclick="Mautic.reorderTableData(
                        '<?php echo $sessionVar; ?>',
                        '<?php echo $orderBy; ?>',
                        '<?php echo $tmpl; ?>',
                        '<?php echo $target; ?>');">
        <span><?php echo $view['translator']->trans($text); ?></span>
        <?php if ($order == $orderBy): ?>
        <i class="fa fa-sort-amount-<?php echo strtolower($dir); ?>"></i>
        <?php endif; ?>
    </a>
</th>