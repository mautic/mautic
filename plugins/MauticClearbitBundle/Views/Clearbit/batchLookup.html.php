<?php

/*
 * @copyright   2015 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
$attr = $form->vars['attr'];
?>
    <div class="alert alert-info"><?php echo $view['translator']->trans('mautic.plugin.clearbit.submit_items'); ?></div>
    <div style="margin-top: 10px">
        <ul class="list-group" style="max-height: 400px;overflow-y: auto">
            <?php
            foreach ($lookupItems as $item) {
                echo '<li class="list-group-item">'.$item.'</li>';
            }
            ?>
        </ul>
    </div>

    <script>
        (function () {
            var ids = Mautic.getCheckedListIds(false, true);
            if (mQuery('#clearbit_batch_lookup_ids').length) {
                mQuery('#clearbit_batch_lookup_ids').val(ids);
            }
        })();
    </script>
<?php
echo $view['form']->form($form, ['attr' => $attr]);
