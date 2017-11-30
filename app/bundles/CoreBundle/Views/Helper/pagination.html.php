<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
$target = (!empty($target)) ? $target : '.page-list';
$tmpl   = (!empty($tmpl)) ? $tmpl : 'list';

if (empty($fixedPages)) {
    $limit = (!isset($limit)) ? 30 : (int) $limit;
    if (!$totalPages = ($limit) ? (int) ceil($totalItems / $limit) : 1) {
        $totalPages = 1;
    }
} else {
    // Fixed number of pages
    $limit      = 1;
    $totalPages = $fixedPages;
}

if (!isset($range)) {
    $range = 5;
}

if ($page <= 0) {
    $page = 1;
} else {
    $page = (int) $page;
}

$linkType            = !empty($inModal) ? 'ajaxmodal' : 'ajax';
$pageClass           = (!isset($paginationClass)) ? '' : " pagination-$paginationClass";
$menuLink            = (!empty($menuLinkId)) ? " data-menu-link=\"$menuLinkId\"" : '';
$paginationWrapper   = isset($paginationWrapper) ? $paginationWrapper : 'pagination-wrapper ml-md mr-md';
$queryString         = '?tmpl='.$tmpl.(isset($queryString) ? $queryString : '');
$formExit            = (!empty($ignoreFormExit)) ? ' data-ignore-formexit="true"' : '';
$responsiveViewports = ['desktop', 'mobile'];
$limitOptions        = [
    5   => '5',
    10  => '10',
    15  => '15',
    20  => '20',
    25  => '25',
    30  => '30',
    50  => '50',
    100 => '100',
];

if (!isset($jsCallback)) {
    $jsCallback = '';
}
if (!isset($jsArguments)) {
    $jsArguments = [];
} else {
    $jsArguments = (array) $jsArguments;
}
if (!isset($baseUrl)) {
    $baseUrl = null;
}

$getAction = function ($page, $active) use ($jsCallback, $jsArguments, $baseUrl, $queryString) {
    if (!$active) {
        return 'href="javascript:void(0);"';
    }

    if ($jsCallback) {
        if ($jsArguments) {
            foreach ($jsArguments as $key => $argument) {
                if (is_array($argument)) {
                    $jsArguments[$key] = json_encode($argument);
                } else {
                    $jsArguments[$key] = "\"{$jsArguments[$key]}\"";
                }
            }

            return 'href="javascript:void(0);"'." onclick='".$jsCallback.'('.implode(',', $jsArguments).", $page, this);'";
        }

        return 'href="javascript:void(0);"'." onclick='".$jsCallback."($page, this);'";
    }

    return "href=\"$baseUrl/$page{$queryString}\"";
};

foreach ($responsiveViewports as $viewport):

    if ($viewport == 'mobile'):
        $paginationClass   = 'sm';
        $pageClass         = 'pagination-sm';
        $responsiveClass   = 'visible-xs hidden-sm hidden-md hidden-lg';
        $paginationWrapper = 'pagination-wrapper pull-left nm';
    else:
        $responsiveClass = 'hidden-xs visible-sm visible-md visible-lg';
    endif;

    ?>
    <div class="<?php echo $responsiveClass; ?>">
        <?php if (empty($fixedLimit)): ?>
            <div class="pull-right">
                <?php $class = (!empty($paginationClass)) ? " input-{$paginationClass}" : ''; ?>
                <select autocomplete="false" class="form-control not-chosen pagination-limit<?php echo $class; ?>" onchange="Mautic.limitTableData('<?php echo $sessionVar; ?>',this.value,'<?php echo $tmpl; ?>','<?php echo $target; ?>'<?php if (!empty($baseUrl)): ?>, '<?php echo $baseUrl; ?>'<?php endif; ?>);">
                    <?php foreach ($limitOptions as $value => $label): ?>
                        <?php $selected = ($limit === $value) ? ' selected="selected"' : ''; ?>
                        <option<?php echo $selected; ?> value="<?php echo $value; ?>">
                            <?php echo $view['translator']->trans('mautic.core.pagination.'.$label); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        <?php endif; ?>

        <div class="<?php echo $paginationWrapper; ?> text-center">
            <ul class="pagination np nm <?php echo $pageClass; ?>">
                <?php
                $action = $getAction(1, ($page > 1));
                $data   = strpos($action, 'javascript:void(0);') !== false ? '' : ' data-toggle="'.$linkType.'" data-target="'.$target.'"'.$menuLink;
                $class  = ($page <= 1) ? ' class="disabled"' : '';
                ?>
                <li<?php echo $class; ?>>
                    <?php ?>
                    <a <?php echo $action; ?><?php echo $data.$formExit; ?>>
                        <i class="fa fa-angle-double-left"></i>
                    </a>
                </li>

                <?php
                $action = $getAction(($page - 1), ($page - 1) >= 1);
                $data   = strpos($action, 'javascript:void(0);') !== false ? '' : ' data-toggle="'.$linkType.'" data-target="'.$target.'"'.$menuLink;
                $class  = (($page - 1) <= 0) ? ' class="disabled"' : '';
                ?>
                <li<?php echo $class; ?>>
                    <?php ?>
                    <a <?php echo $action; ?><?php echo $data.$formExit; ?>>
                        <i class="fa fa-angle-left"></i>
                    </a>
                </li>

                <?php
                $startPage = $page - ceil($range / 2) + 1;
                if ($startPage <= 0) {
                    $startPage = 1;
                }
                $lastPage = $startPage + $range - 1;
                if ($lastPage > $totalPages) {
                    $lastPage = $totalPages;
                }
                ?>
                <?php for ($i = $startPage; $i <= $lastPage; ++$i): ?>
                    <?php
                    $class  = ($page === (int) $i) ? ' class="active"' : '';
                    $action = $getAction($i, ($page !== (int) $i));
                    $data   = strpos($action, 'javascript:void(0);') !== false ? '' : ' data-toggle="'.$linkType.'" data-target="'.$target.'"'.$menuLink;
                    ?>
                    <li<?php echo $class; ?>>
                        <a <?php echo $action; ?><?php echo $data.$formExit; ?>>
                            <span><?php echo $i; ?></span>
                        </a>
                    </li>
                <?php endfor; ?>

                <?php
                $action = $getAction(($page + 1), (($page + 1) <= $totalPages));
                $data   = strpos($action, 'javascript:void(0);') !== false ? '' : ' data-toggle="'.$linkType.'" data-target="'.$target.'"'.$menuLink;
                $class  = (($page + 1) > $totalPages) ? ' class="disabled"' : '';
                ?>
                <li<?php echo $class; ?>>
                    <?php ?>
                    <a <?php echo $action; ?><?php echo $data.$formExit; ?>>
                        <i class="fa fa-angle-right"></i>
                    </a>
                </li>

                <?php
                $action = $getAction($totalPages, ($page < $totalPages));
                $data   = strpos($action, 'javascript:void(0);') !== false ? '' : ' data-toggle="'.$linkType.'" data-target="'.$target.'"'.$menuLink;
                $class  = ($page === $totalPages) ? ' class="disabled"' : '';
                ?>
                <li<?php echo $class; ?>>
                    <?php ?>
                    <a <?php echo $action; ?><?php echo $data.$formExit; ?>>
                        <i class="fa fa-angle-double-right"></i>
                    </a>
                </li>
            </ul>
            <div class="clearfix"></div>
            <small class="text-muted">
                <?php echo $view['translator']->transChoice(
                    'mautic.core.pagination.items',
                    $totalItems,
                    ['%count%' => $totalItems]
                ); ?>,
                <?php echo $view['translator']->transChoice(
                    'mautic.core.pagination.pages',
                    $totalPages,
                    ['%count%' => $totalPages]
                ); ?>
                <?php echo $view['translator']->trans(
                    'mautic.core.pagination.total'
                ); ?>
            </small>
        </div>
    </div>
<?php endforeach; ?>
