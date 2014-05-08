<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

//$items should be a Doctrine paginator instance
if (!isset($limit)) {
    $limit = 30;
}

$totalItems = count($items);
$totalPages = (int) ceil($totalItems / $limit);
if ($totalPages > 1):

$prevClass  = "";
$prevUrl    = $baseUrl . "/" . ($page - 1);
$nextClass  = "";
$nextUrl    = $baseUrl . "/" . ($page + 1);

if ((int) $page === 1) {
    $prevClass = ' class="disabled"';
    $prevUrl   = '#';
} elseif ((int) $page === $totalPages) {
    $nextClass = ' class="disabled"';
    $nextUrl   = '#';
}

$paginationClass = (!isset($paginationClass)) ? "" : " $paginationClass";
?>

<?php if (!empty($totalItems)): ?>
<div class="clearfix"></div>
<div class="pagination-wrapper">
    <ul class="pagination pull-right<?php echo $paginationClass; ?>">
        <li<?php echo $prevClass;?>>
            <a href="<?php echo $prevUrl; ?>" data-toggle="ajax" data-menu-link="#mautic_user_index">&laquo;</a>
        </li>
        <?php for ($i=1; $i<=$totalPages; $i++): ?>
        <li<?php echo ((int) $page === $i) ? ' class="active"' : ''; ?>>
            <a href="<?php echo $baseUrl . "/" . $i; ?>" data-toggle="ajax" data-menu-link="#mautic_user_index">
                <?php echo $i; ?>
            </a>
        </li>
        <?php endfor; ?>
        <li<?php echo $nextClass; ?>>
            <a href="<?php echo $nextUrl; ?>" data-toggle="ajax" data-menu-link="#mautic_user_index");">&raquo;</a>
        </li>
    </ul>
    <div class="clearfix"></div>
</div>
<?php endif; ?>

<?php endif; ?>