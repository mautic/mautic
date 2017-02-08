<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
?>

<div class="nav-overflow-tabs">
    <ul class="bg-auto nav nav-tabs pr-md pl-md">
        <?php foreach ($tabs as $tab): ?>
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
            <li class="<?php if (!empty($tab['active'])): echo 'active'; endif; ?><?php echo $class; ?>"<?php echo $attr; ?>>
                <a href="#<?php echo $tab['id']; ?>" role="tab" data-toggle="tab" class="<?php echo $class; ?>">
                    <?php echo $tab['name']; ?>
                    <?php if (!empty($tab['icon'])): ?>
                        <i class="fa <?php echo $tab['icon']; ?>"></i>
                    <?php endif; ?>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>
</div>
<div class="tab-content pa-md">
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
</div>
