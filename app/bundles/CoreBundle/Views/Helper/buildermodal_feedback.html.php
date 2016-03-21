<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

echo $view->render('MauticCoreBundle:Helper:modal.html.php', array(
    'id'     => 'BuilderFeedbackModal',
    'header' => false,
    'body'   => <<<BODY
<div class="row">
    <div class="col-lg-12">
        <input name="feedback" type="text" class="form-control" />
    </div>

    <input type="hidden" name="editor" value="" />
    <input type="hidden" name="token" value="" />
</div>
BODY
,
    'footer' => <<<FOOTER
<button class="btn btn-default" data-dismiss="modal" type="button">{$view['translator']->trans('mautic.core.form.cancel')}</button>
<button class="btn btn-primary" onclick="Mautic.insertBuilderFeedback();" type="button">{$view['translator']->trans('mautic.core.form.insert')}</button>
FOOTER
));