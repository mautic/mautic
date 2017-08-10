<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
$view->extend('MauticCoreBundle:Default:content.html.php');
$view['slots']->set('mauticContent', 'mauticWebhook');

$header = ($entity->getId()) ?
    $view['translator']->trans('mautic.webhook.webhook.header.edit',
        ['%name%' => $view['translator']->trans($entity->getName())]) :
    $view['translator']->trans('mautic.webhook.webhook.header.new');

$view['slots']->set('headerTitle', $header);

?>

<?php echo $view['form']->start($form); ?>
<!-- start: box layout -->
<div class="box-layout">
    <!-- container -->
    <div class="col-md-9 bg-auto height-auto">
        <div class="pa-md">
            <div class="row">
                <div class="col-md-6">
                    <?php echo $view['form']->row($form['name']); ?>
                    <?php echo $view['form']->row($form['description']); ?>
                    <?php echo $view['form']->row($form['webhookUrl']); ?>
                    <div class="row">
                        <div class="col-md-5">
                            <?php echo $view['form']->row($form['sendTest']); ?>
                        </div>
                        <div class="col-md-2">
                            <span id="spinner" class="fa fa-spinner fa-spin hide"></span>
                        </div>
                        <div class="col-md-5">
                            <div id="tester" class="text-right"></div>
                        </div>
                    </div>

                </div>
                <div class="col-md-6" id="event-types">
                    <?php echo $view['form']->row($form['events']); ?>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 bg-white height-auto bdr-l">
        <div class="pr-lg pl-lg pt-md pb-md">
            <?php echo $view['form']->row($form['category']); ?>
            <?php echo $view['form']->row($form['eventsOrderbyDir']); ?>
            <?php echo $view['form']->row($form['isPublished']); ?>
        </div>
    </div>
</div>
<?php echo $view['form']->end($form); ?>