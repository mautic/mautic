<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
?>
<div id="pageTokens">
    <ul class="list-group">
        <li class="list-group-item" data-toggle="tooltip" data-token="{langbar}" data-placement="bottom" title="<?php echo $view['translator']->trans('mautic.page.page.token.lang.descr'); ?>">
            <div class="padding-sm">
                <span><i class="fa fa-language fa-fw"></i><?php echo $view['translator']->trans('mautic.page.page.token.lang'); ?></span>
            </div>
        </li>
        <li class="list-group-item" data-toggle="tooltip" data-token="{sharebuttons}" data-placement="bottom" title="<?php echo $view['translator']->trans('mautic.page.page.token.share.descr'); ?>">
            <div class="padding-sm">
                <i class="fa fa-share-alt-square fa-fw"></i><?php echo $view['translator']->trans('mautic.page.page.token.share'); ?>
            </div>
        </li>
    </ul>
</div>