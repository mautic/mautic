<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

$defaultOrder = (!empty($default)) ? $orderBy : "";
$order        = $app->getSession()->get("mautic.{$entity}.orderby", $defaultOrder);
$dir          = $app->getSession()->get("mautic.{$entity}.orderbydir", "ASC");
?>
<th<?php echo (!empty($class)) ? ' class="' . $class . '"': ""; ?>>
    <a href="javascript: void(0);" onclick="Mautic.reorderTableData(
                        '<?php echo $entity; ?>',
                        '<?php echo $orderBy; ?>');">
        <span><?php echo $view['translator']->trans($text); ?></span>
        <?php if ($order == $orderBy): ?>
        <i class="fa fa-sort-amount-<?php echo strtolower($dir); ?>"></i>
        <?php endif; ?>
    </a>
</th>