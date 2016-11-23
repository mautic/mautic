<?php

/*
 * @copyright   2015 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

$count = count($types);
if ($count === 2 || $count === 4) {
    $modalSize = 'md';
    $colSize = 6;
} else {
    $modalSize = 'lg';
    $colSize = 4;
}
?>
<script>
    <?php foreach ($mauticLang as $key => $string): ?>
    mauticLang.<?php echo $key; ?> = "<?php echo $view['translator']->trans($string); ?>";
    <?php endforeach; ?>
</script>
<div class="<?php echo $typePrefix; ?>-type-modal-backdrop" style="width: 100%; height: 100%; position: absolute; top: 0; left: 0; right: 0; bottom: 0; background-color: #000000; opacity: 0.9; z-index: 9000"></div>

<div class="modal fade in <?php echo $typePrefix; ?>-type-modal" style="display: block; z-index: 9999;">
    <div class="modal-dialog modal-<?php echo $modalSize ?>">
        <div class="modal-content">
            <div class="modal-header">
                <a href="javascript: void(0);" onclick="Mautic.closeModalAndRedirect('.<?php echo $typePrefix; ?>-type-modal', '<?php echo $view['router']->path($cancelUrl); ?>');" class="close" ><span aria-hidden="true">&times;</span></a>
                <h4 class="modal-title">
                    <?php echo $view['translator']->trans($header); ?>
                </h4>
                <div class="modal-loading-bar"></div>
            </div>
            <div class="modal-body form-select-modal">
                <div class="row">
                    <?php foreach ($types as $type): ?>
                        <div class="col-md-<?php echo $colSize ?>">
                            <div class="panel panel-<?php echo $type['color'] ?>">
                                <div class="panel-heading">
                                    <div class="col-xs-8 col-sm-10 np">
                                        <h3 class="panel-title"><?php echo $view['translator']->trans($type['header']); ?></h3>
                                    </div>
                                    <div class="col-xs-4 col-sm-2 pl-0 pr-0 pt-10 pb-10 text-right">
                                        <i class="hidden-xs fa <?php echo $type['iconClass']; ?> fa-lg"></i>
                                        <button class="visible-xs pull-right btn btn-sm btn-default btn-nospin text-primary" onclick="<?php echo $type['onClick']; ?>"><?php echo $view['translator']->trans('mautic.core.select'); ?></button>
                                    </div>
                                </div>
                                <div class="panel-body">
                                    <?php echo $view['translator']->trans($type['description']); ?>
                                </div>
                                <div class="hidden-xs panel-footer text-center">
                                    <button class="btn btn-lg btn-default btn-nospin text-<?php echo $type['color'] ?>" onclick="<?php echo $type['onClick']; ?>"><?php echo $view['translator']->trans('mautic.core.select'); ?></button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>
