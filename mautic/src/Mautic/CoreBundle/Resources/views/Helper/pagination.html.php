<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

$target = (!empty($target)) ? $target : '.bundle-list';
$tmpl   = (!empty($tmpl)) ? $tmpl : 'content';

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

$totalItems  = (!isset($totalItems)) ? count($items) : $totalItems;
$totalPages = ($limit) ? (int) ceil($totalItems / $limit) : 1;

$pageClass = (!isset($paginationClass)) ? "" : " pagination-$paginationClass";
$menuLink  = (!empty($menuLinkId)) ? " data-menu-link=\"$menuLinkId\"" : "";

if (isset($queryString)) {
    $queryString = '?' . $queryString;
} else {
    $queryString = '';
}

$limitOptions = array(
    5   => '5',
    10  => '10',
    15  => '15',
    20  => '20',
    25  => '25',
    30  => '30',
    50  => '50',
    100 => '100',
    0   => 'all'
);

?>
<div class="clearfix"></div>
<div class="pagination-wrapper">
    <ul class="pagination pagination-centered <?php echo $pageClass; ?>">
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
        $class   = ($urlPage == $totalPages || $page === $totalPages) ? ' class="disabled"' : '';
        ?>
        <li<?php echo $class; ?>>
            <?php  ?>
            <a href="<?php echo $url; ?>"<?php echo $data; ?>>
                <i class="fa fa-angle-double-right"></i>
            </a>
        </li>
        <li>
            <?php $class = (!empty($paginationClass)) ? " input-{$paginationClass}" : ""; ?>
            <select autocomplete="off" class="form-control pagination-limit<?php echo $class; ?>" onchange="Mautic.limitTableData(
                '<?php echo $sessionVar; ?>',
                this.value,
                '<?php echo $tmpl; ?>',
                '<?php echo $target; ?>'
                );">
                <?php foreach ($limitOptions as $value => $label): ?>
                <?php $selected = ($limit === $value) ? ' selected="selected"': '';?>
                <option<?php echo $selected; ?> value="<?php echo $value; ?>"><?php echo $view['translator']->trans('mautic.core.pagination.'.$label); ?></option>
                <?php endforeach; ?>
            </select>

        </li>

    </ul>
    <div class="clearfix"></div>
</div>