<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

use Mautic\CoreBundle\Helper\BuilderTokenHelper;

?>
<div id="emailEmailTokens">
    <div class="row ml-2 mr-2 mb-2">
        <div class="col-sm-6">
            <?php $token = $view->escape(BuilderTokenHelper::getVisualTokenHtml('{unsubscribe_text}', $view['translator']->trans('mautic.email.token.unsubscribe_text'))); ?>
            <a href="#" data-toggle="tooltip" data-token="<?php echo $token; ?>" class="btn btn-default btn-block" title="<?php echo $view['translator']->trans('mautic.email.token.unsubscribe_text.descr'); ?>">
                <i class="fa fa-file-text-o"></i><br />
                <?php echo $view['translator']->trans('mautic.email.token.unsubscribe_text'); ?>
            </a>
        </div>
        <div class="col-sm-6">
            <a href="#" data-toggle="tooltip" data-token='<a href="%url={unsubscribe_url}%">%text%</a>' data-drop="showBuilderLinkModal" class="btn btn-default btn-block" title="<?php echo $view['translator']->trans('mautic.email.token.unsubscribe_url.descr'); ?>">
                <i class="fa fa-link"></i><br />
                <?php echo $view['translator']->trans('mautic.email.token.unsubscribe_url'); ?>
            </a>
        </div>
    </div>
    <div class="row ml-2 mr-2">
        <div class="col-sm-6">
            <?php $token = $view->escape(BuilderTokenHelper::getVisualTokenHtml('{webview_text}', $view['translator']->trans('mautic.email.token.webview_text'))); ?>
            <a href="#" data-toggle="tooltip" data-token="<?php echo $token; ?>" class="btn btn-default btn-block" title="<?php echo $view['translator']->trans('mautic.email.token.webview_text.descr'); ?>">
                <i class="fa fa-file-text-o"></i><br />
                <?php echo $view['translator']->trans('mautic.email.token.webview_text'); ?>
            </a>
        </div>
        <div class="col-sm-6">
            <a href="#" data-toggle="tooltip" data-token='<a href="%url={webview_url}%">%text%</a>' data-drop="showBuilderLinkModal" class="btn btn-default btn-block" title="<?php echo $view['translator']->trans('mautic.email.token.webview_url.descr'); ?>">
                <i class="fa fa-link"></i><br />
                <?php echo $view['translator']->trans('mautic.email.token.webview_url'); ?>
            </a>
        </div>
    </div>
</div>