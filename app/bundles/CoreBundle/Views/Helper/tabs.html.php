<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

$overflow = isset($overflow) ? 'nav-overflow-tabs' : '';
if (!isset($verticalTabColWidth)) {
    $verticalTabColWidth = 3;
}
$verticalContentColWidth = 12 - (int) $verticalTabColWidth;
?>
<?php if (!empty($vertical)): ?>
<div class="box-layout">
<?php endif; ?>
<div class="tab-container <?php echo $overflow; ?><?php echo !empty($vertical) ? ' bg-auto height-auto col-xs-'.$verticalTabColWidth.' pr-0 bdr-r' : ''; ?>">
    <?php if (!empty($button)): ?>
    <div class="tab-button<?php echo (!empty($vertical)) ? ' tab-button-'.$vertical : ''; ?>">
        <a href="javascript:void(0);"
           role="tab"
           class="btn btn-primary btn-lg btn-block btn-nospin"
           id="<?php echo $button['id']; ?>"
           style="border-radius: 0;"
            <?php if (!empty($button['attr'])): echo $button['attr']; endif; ?>
        >
            <i class="fa fa-fw <?php echo $button['icon']; ?>"></i><?php echo $button['text']; ?>
        </a>
    <?php if (!empty($button['extra'])): echo $button['extra']; endif; ?>
    </div>
    <?php endif; ?>
    <ul<?php echo (isset($deletable) && is_string($deletable)) ? ' data-delete-action="'.$deletable.'" ' : ''; ?>
        <?php echo (isset($sortable) && is_string($sortable)) ? ' data-sort-action="'.$sortable.'" ' : ''; ?>
            class="<?php echo (!empty($deletable)) ? 'nav-deletable ' : ''; ?>nav nav-tabs <?php echo (!empty($vertical)) ? 'tabs-'.$vertical.' pt-0 bdr-b-wdh-0  bdr-r-wdh-0' : 'tabs-horizontal bg-auto'; ?><?php echo !empty($sortable) ? ' sortable' : ''; ?>">
        <?php foreach ($tabs as $tabKey => $tab): ?>
            <?php
            $class = (!empty($tab['class'])) ? ' '.$tab['class'] : '';
            if (isset($tab['attr']) && is_array($tab['attr'])) {
                $attr = [];
                foreach ($tab['attr'] as $key => $val) {
                    $attr[] = "$key=\"$val\"";
                }
                $tab['attr'] = implode(' ', $attr);
            }
            $attr = (!empty($tab['attr'])) ? ' '.$tab['attr'] : '';
            if (!isset($tab['icon'])) {
                if (!empty($tab['published'])) {
                    $tab['icon'] = 'fa-check-circle text-success';
                } else {
                    $tab['icon'] = 'fa-check-circle text-muted';
                }
            }
            ?>
            <li data-tab-id="<?php echo $tab['id']; ?>" class="<?php if (!empty($tab['active'])): echo 'active'; endif; ?><?php echo $class; ?>"<?php echo $attr; ?>>
                <a href="#<?php echo $tab['id']; ?>" role="tab" data-toggle="tab" class="<?php echo $class; ?>">
                    <span><?php echo $tab['name']; ?></span>
                    <?php if (!empty($tab['icon'])): ?>
                        <i class="fa fa-fw <?php echo $tab['icon']; ?>"></i>
                    <?php endif; ?>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>
</div>
<div class="tab-content<?php echo !empty($vertical) ? ' col-xs-'.$verticalContentColWidth.' pl-0 pt-0 height-auto' : ' pa-md'; ?>">
    <?php foreach ($tabs as $tab): ?>
        <?php
        $containerClass = (!empty($tab['containerClass'])) ? ' '.$tab['containerClass'] : '';
        if (isset($tab['containerAttr']) && is_array($tab['containerAttr'])) {
            $attr = [];
            foreach ($tab['containerAttr'] as $key => $val) {
                $attr[] = "$key=\"$val\"";
            }
            $tab['containerAttr'] = implode(' ', $attr);
        }
        $containerAttr = (!empty($tab['containerAttr'])) ? ' '.$tab['containerAttr'] : '';
        ?>
        <div class="tab-pane fade <?php echo (!empty($tab['active'])) ? 'in active' : ''; ?> bdr-w-0<?php echo $containerClass; ?>" id="<?php echo $tab['id']; ?>"<?php echo $containerAttr; ?>>
            <?php echo $tab['content']; ?>
        </div>
    <?php endforeach; ?>

    <?php if (!empty($noContentKey)): ?>
    <div class="placeholder<?php echo empty($tabs) ? '' : ' hide'; ?>">
        <div class="alert alert-warning">
            <?php echo $view['translator']->trans($noContentKey); ?>
        </div>
    </div>
    <?php endif; ?>
</div>
<?php if (!empty($vertical)): ?>
</div>
<?php endif; ?>
