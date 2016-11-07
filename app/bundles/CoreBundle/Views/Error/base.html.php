<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
if (!$app->getRequest()->isXmlHttpRequest()) {
    $view->extend($baseTemplate);
    $view['slots']->set('pageTitle', $status_text);
}

$img = $view['slots']->get('mautibot', 'wave');
$src = $view['mautibot']->getImage($img);

$message = $view['slots']->get('message', 'mautic.core.error.generic');

?>
<div class="pa-20 mautibot-error">
    <div class="row mt-lg pa-md">
        <div class="mautibot-image col-xs-4 col-md-3">
            <img class="img-responsive" src="<?php echo $src; ?>" />
        </div>
        <div class="mautibot-content col-xs-8 col-md-9">
            <blockquote class="np break-word">
                <h1><i class="fa fa-quote-left"></i> <?php echo $view['translator']->trans($message, ['%code%' => $status_code]); ?> <i class="fa fa-quote-right"></i></h1>
                <h4 class="mt-5"><strong><?php echo $status_code; ?></strong> <?php echo $status_text; ?></h4>

                <footer class="text-right">Mautibot</footer>
            </blockquote>
            <div class="pull-right">
                <a class="text-muted" href="http://mau.tc/report-issue" target="_new"><?php echo $view['translator']->trans('mautic.core.report_issue'); ?></a>
            </div>
        </div>
    </div>
</div>