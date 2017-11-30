<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

/** @var $exception \Symfony\Component\HttpKernel\Exception\FlattenException */
/** @var $logger \Symfony\Component\HttpKernel\Log\DebugLoggerInterface */
$message            = $view['slots']->get('message', 'mautic.core.error.generic');
$previousExceptions = $exception->getAllPrevious();

$exceptionMessage = $exception->getMessage();
if ($exceptionMessage) {
    $exceptionMessage = ' - '.$exceptionMessage;
}
$isInline = !empty($inline);
if (!$app->getRequest()->isXmlHttpRequest()) {
    $view->extend($baseTemplate);

    if (!$isInline) {
        $view['slots']->set('pageTitle', $exceptionMessage);

        $header = "<strong>$status_code</strong> $status_text";
        $view['slots']->set('headerTitle', $header);
    }
}

$img = $view['slots']->get('mautibot', 'wave');
$src = $view['mautibot']->getImage($img);
?>

<div class="pa-20 mautibot-error<?php if ($isInline): echo ' inline'; endif; ?>">
    <div class="row">
        <?php if ($isInline): ?>
        <div class="mautibot-content col-xs-12">
            <h3><i class="fa fa-warning fa-fw text-danger"></i><strong><?php echo $status_code; ?></strong> <?php echo $status_text; ?><?php echo $exceptionMessage; ?></h3>
        </div>
        <?php else: ?>
        <div class="mautibot-image col-xs-4 col-md-3">
            <img class="img-responsive mautibot" src="<?php echo $src; ?>" />
        </div>
        <div class="mautibot-content col-xs-8 col-md-9">
            <blockquote class="np break-word">
                <h2><i class="fa fa-quote-left"></i> <strong><?php echo $status_code; ?></strong> <?php echo $status_text; ?><?php echo $exceptionMessage; ?> <i class="fa fa-quote-right"></i></h2>
                <footer class="text-right">Mautibot</footer>
            </blockquote>
            <div class="pull-right">
                <a class="text-muted" href="http://mau.tc/report-issue" target="_new"><?php echo $view['translator']->trans('mautic.core.report_issue'); ?></a>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <div<?php if ($isInline): echo ' class="slimscroll" style="max-height: 200px;"'; endif; ?>>
        <div class="row mt-20">
            <div class="col-sm-12">
                <h5 class="ml-lg text-danger"><?php echo $exception->getClass(); ?></h5>
                <div class="well well-sm ma-md">
                    <?php echo $view->render('MauticCoreBundle:Exception:traces.html.php', [
                        'traces' => $exception->getTrace(),
                    ]); ?>
                </div>
            </div>
        </div>

        <?php if (count($previousExceptions)): ?>
        <div class="row mt-20">
            <div class="col-sm-12">
                <h5 class="ml-lg"><?php echo $view['translator']->trans('mautic.core.error.previousexceptions'); ?></h5>
                <div class="panel-group" id="previous" role="tablist" aria-multiselectable="true">
                <?php foreach ($previousExceptions as $key => $e): ?>
                    <div class="panel panel-default">
                        <div class="panel-heading" role="tab" id="previous_heading_<?php echo $key; ?>">
                            <h4 class="panel-title pa-sm">
                                <a data-toggle="collapse" data-parent="#previous" href="#previous_body_<?php echo $key; ?>" aria-expanded="true" aria-controls="collapseOne">
                                    <?php echo $e->getMessage(); ?>
                                </a>
                            </h4>
                        </div>
                        <div id="previous_body_<?php echo $key; ?>" class="panel-collapse collapse" role="tabpanel" aria-labelledby="headingOne">
                            <div class="panel-body">
                                <div class="pa-sm">
                                    <?php echo $view->render('MauticCoreBundle:Exception:traces.html.php', [
                                        'traces' => $e->getTrace(),
                                    ]); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
        </div>
    </div>
</div>
<div class="clearfix"></div>