<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
?>
<div id="emailEmailTokens">
    <p class="row">
        <div class="col-sm-6">
            <a href="#" data-toggle="tooltip" data-token="{unsubscribe_text}" class="btn btn-default btn-block" title="<?php echo $view['translator']->trans('mautic.email.token.unsubscribe_text.descr'); ?>">
                <i class="fa fa-file-text-o"></i><br />
                <?php echo $view['translator']->trans('mautic.email.token.unsubscribe_text'); ?>
            </a>
        </div>
        <div class="col-sm-6">
            <a href="#" data-toggle="tooltip" data-token="{unsubscribe_url}" class="btn btn-default btn-block" title="<?php echo $view['translator']->trans('mautic.email.token.unsubscribe_url.descr'); ?>">
                <i class="fa fa-link"></i><br />
                <?php echo $view['translator']->trans('mautic.email.token.unsubscribe_url'); ?>
            </a>
        </div>
    </div>
    <p class="row">
        <div class="col-sm-6">
            <a href="#" data-toggle="tooltip" data-token="{webview_text}" class="btn btn-default btn-block" title="<?php echo $view['translator']->trans('mautic.email.token.webview_text.descr'); ?>">
                <i class="fa fa-file-text-o"></i><br />
                <?php echo $view['translator']->trans('mautic.email.token.webview_text'); ?>
            </a>
        </div>
        <div class="col-sm-6">
            <a href="#" data-toggle="tooltip" data-token="{webview_url}" class="btn btn-default btn-block" title="<?php echo $view['translator']->trans('mautic.email.token.webview_url.descr'); ?>">
                <i class="fa fa-link"></i><br />
                <?php echo $view['translator']->trans('mautic.email.token.webview_url'); ?>
            </a>
        </div>
    </p>
</div>