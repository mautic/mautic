<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
?>
<div class="page-list" id="form-email-tokens">
    <ul class="draggable scrollable">
        <li class="page-list-item has-click-event">
            <div class="padding-sm">
                <span class="list-item-primary">
                    <?php echo $view['translator']->trans('mautic.email.token.unsubscribe_text'); ?>
                </span>
                <span class="list-item-secondary" data-toggle="tooltip" data-placement="bottom"
                      title="<?php echo $view['translator']->trans('mautic.email.token.unsubscribe_text.descr'); ?>">
                    <?php echo substr($view['translator']->trans('mautic.email.token.unsubscribe_text.descr'), 0, 30); ?>...
                </span>
                <input type="hidden" class="email-token" value="{unsubscribe_text}" />
            </div>
        </li>
        <li class="page-list-item has-click-event">
            <div class="padding-sm">
                <span class="list-item-primary">
                    <?php echo $view['translator']->trans('mautic.email.token.unsubscribe_url'); ?>
                </span>
                <span class="list-item-secondary" data-toggle="tooltip" data-placement="bottom"
                      title="<?php echo $view['translator']->trans('mautic.email.token.unsubscribe_url.descr'); ?>">
                    <?php echo substr($view['translator']->trans('mautic.email.token.unsubscribe_url.descr'), 0, 30); ?>...
                </span>
                <input type="hidden" class="email-token" value="{unsubscribe_url}" />
            </div>
        </li>
        <li class="page-list-item has-click-event">
            <div class="padding-sm">
                <span class="list-item-primary">
                    <?php echo $view['translator']->trans('mautic.email.token.webview_text'); ?>
                </span>
                <span class="list-item-secondary" data-toggle="tooltip" data-placement="bottom"
                      title="<?php echo $view['translator']->trans('mautic.email.token.webview_text.descr'); ?>">
                    <?php echo substr($view['translator']->trans('mautic.email.token.webview_text.descr'), 0, 30); ?>...
                </span>
                <input type="hidden" class="email-token" value="{webview_text}" />
            </div>
        </li>
        <li class="page-list-item has-click-event">
            <div class="padding-sm">
                <span class="list-item-primary">
                    <?php echo $view['translator']->trans('mautic.email.token.webview_url'); ?>
                </span>
                <span class="list-item-secondary" data-toggle="tooltip" data-placement="bottom"
                      title="<?php echo $view['translator']->trans('mautic.email.token.webview_url.descr'); ?>">
                    <?php echo substr($view['translator']->trans('mautic.email.token.webview_url.descr'), 0, 30); ?>...
                </span>
                <input type="hidden" class="email-token" value="{webview_url}" />
            </div>
        </li>
    </ul>
</div>