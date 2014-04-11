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
?>

<?php if (!empty($totalItems)): ?>
<div class="clearfix"></div>
<div class="pagination-wrapper">
    <ul class="pagination pull-right ">
        <li<?php echo $prevClass;?>>
            <a href="javascript: void(0);"
               onclick="Mautic.loadContent('<?php echo $prevUrl; ?>', '#mautic_user_index', false);">&laquo;</a>
        </li>
        <?php for ($i=1; $i<=$totalPages; $i++): ?>
        <li<?php echo ((int) $page === $i) ? ' class="active"' : ''; ?>>
            <a href="javascript: void(0);"
               onclick="Mautic.loadContent('<?php echo $baseUrl . "/" . $i;; ?>', '#mautic_user_index', false);"><?php echo $i; ?></a>
        </li>
        <?php endfor; ?>
        <li<?php echo $nextClass; ?>>
            <a href="javascript: void(0);"
               onclick="Mautic.loadContent('<?php echo $nextUrl; ?>', '#mautic_user_index', false);">&raquo;</a>
        </li>
    </ul>
    <div class="clearfix"></div>
</div>
<?php endif; ?>

<?php endif; ?>