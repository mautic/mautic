<div class="card-flex widget" data-widget-id="<?php echo $widget->getId(); ?>" style="width: <?php echo $widget->getWidth() ? $widget->getWidth().'' : '100' ?>%; height: <?php echo $widget->getHeight() ? $widget->getHeight().'px' : '300px' ?>">
    <?php echo $view->render('MauticDashboardBundle:Widget:detail.html.php', ['widget' => $widget]); ?>
</div>
