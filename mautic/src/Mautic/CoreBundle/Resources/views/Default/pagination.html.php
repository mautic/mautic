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

if (!isset($range)) {
    $range = 4;
}

if ($page <= 0) {
    $page = 1;
} else {
    $page = (int) $page;
}

$baseUrl .= "/";

$totalItems  = count($items);
$totalPages  = (int) ceil($totalItems / $limit);
if ($totalPages <= 1)
    return;

$paginationClass = (!isset($paginationClass)) ? "" : " $paginationClass";
$menuLink = (!empty($menuLinkId)) ? " data-menu-link=\"$menuLinkId\"" : "";

if (isset($queryString)) {
    $queryString = '?' . $queryString;
} else {
    $queryString = '';
}

?>
<div class="clearfix"></div>
<div class="pagination-wrapper">
    <ul class="pagination pagination-centered <?php echo $paginationClass; ?>">
        <?php
        $urlPage = $page - $range;
        $url     = ($urlPage > 0) ? $baseUrl . $urlPage . $queryString : 'javascript: void(0);';
        $data    = ($url == 'javascript: void(0);') ? '' : ' data-toggle="ajax"' . $menuLink;
        $class   = ($urlPage <= 0) ? ' class="disabled"' : '';
        ?>
        <li<?php echo $class; ?>>
            <?php  ?>
            <a href="<?php echo $url; ?>"<?php echo $data; ?>>
                <i class="fa fa-angle-double-left"></i>
            </a>
        </li>

        <?php
        $urlPage = $page - 1;
        $url     = ($urlPage >= 1) ? $baseUrl . $urlPage . $queryString : 'javascript: void(0);';
        $data    = ($url == 'javascript: void(0);') ? '' : ' data-toggle="ajax"' . $menuLink;
        $class   = ($urlPage <= 0) ? ' class="disabled"' : '';
        ?>
        <li<?php echo $class; ?>>
            <?php  ?>
            <a href="<?php echo $url; ?>"<?php echo $data; ?>>
                <i class="fa fa-angle-left"></i>
            </a>
        </li>

        <?php
        $startPage = $page - $range + 1;
        if ($startPage <= 0) {
            $startPage = 1;
        }
        $lastPage = $startPage + $range - 1;
        if ($lastPage > $totalPages) {
            $lastPage = $totalPages;
        }
        ?>
        <?php for ($i=$startPage; $i<=$lastPage; $i++): ?>
        <?php
        $class = ($page === (int) $i) ? ' class="active"' : '';
        $url   = ($page === (int) $i) ? 'javascript: void(0);' : $baseUrl . $i . $queryString;
        $data  = ($url == 'javascript: void(0);') ? '' : ' data-toggle="ajax"' . $menuLink;
        ?>
        <li<?php echo $class; ?>>
            <a href="<?php echo $url; ?>"<?php echo $data; ?>>
                <span><?php echo $i; ?></span>
            </a>
        </li>
        <?php endfor; ?>

        <?php
        $urlPage = $page + 1;
        $url     = ($urlPage <= $totalPages) ? $baseUrl . $urlPage . $queryString : 'javascript: void(0);';
        $data    = ($url == 'javascript: void(0);') ? '' : 'data-toggle="ajax"' . $menuLink;
        $class   = ($urlPage > $totalPages) ? ' class="disabled"' : '';
        ?>
        <li<?php echo $class; ?>>
            <?php  ?>
            <a href="<?php echo $url; ?>" <?php echo $data; ?>>
                <i class="fa fa-angle-right"></i>
            </a>
        </li>

        <?php
        $urlPage = $page + $range;
        if ($urlPage > $totalPages)
            $urlPage = $totalPages;
        $url     = ($page < $totalPages && $totalPages > $range) ? $baseUrl . $urlPage . $queryString : 'javascript: void(0);';
        $data    = ($url == 'javascript: void(0);') ? '' : ' data-toggle="ajax"' . $menuLink;
        $class   = ($urlPage > $totalPages || $page === $totalPages) ? ' class="disabled"' : '';
        ?>
        <li<?php echo $class; ?>>
            <?php  ?>
            <a href="<?php echo $url; ?>"<?php echo $data; ?>>
                <i class="fa fa-angle-double-right"></i>
            </a>
        </li>
    </ul>
    <div class="clearfix"></div>
</div>