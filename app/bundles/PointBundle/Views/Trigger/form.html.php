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
$view['slots']->set('mauticContent', 'pointTrigger');

$header = ($entity->getId()) ?
    $view['translator']->trans('mautic.point.trigger.header.edit',
        ['%name%' => $view['translator']->trans($entity->getName())]) :
    $view['translator']->trans('mautic.point.trigger.header.new');
$view['slots']->set('headerTitle', $header);
?>


<?php echo $view['form']->start($form); ?>

<div class="box-layout">
    <div class="col-md-9 bg-white height-auto">
        <div class="row">
            <div class="col-xs-12">
                <!-- tabs controls -->
                <ul class="bg-auto nav nav-tabs pr-md pl-md">
                    <li class="active"><a href="#details-container" role="tab" data-toggle="tab"><?php echo $view['translator']->trans('mautic.core.details'); ?></a></li>
                    <li class=""><a href="#events-container" role="tab" data-toggle="tab"><?php echo $view['translator']->trans('mautic.point.trigger.tab.events'); ?></a></li>
                </ul>
                <!--/ tabs controls -->

                <div class="tab-content pa-md">
                    <div class="tab-pane fade in active bdr-w-0 height-auto" id="details-container">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="pa-md">
                                    <?php
                                    echo $view['form']->row($form['name']);
                                    echo $view['form']->row($form['description'], ['attr' => ['class' => 'form-control editor']]);
                                    ?>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="pa-md">
                                    <?php
                                    echo $view['form']->row($form['points']);
                                    echo $view['form']->row($form['color']);
                                    echo $view['form']->row($form['triggerExistingLeads']);
                                    ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="tab-pane fade bdr-w-0" id="events-container">
                        <div id="triggerEvents">
                            <div class="mb-md">
                                <p><?php echo $view['translator']->trans('mautic.point.trigger.addevent'); ?></p>
                                <div class="dropdown">
                                    <button class="btn btn-default dropdown-toggle" type="button" data-toggle="dropdown">
                                        <?php echo $view['translator']->trans('mautic.point.trigger.event.add'); ?>
                                        <span class="caret"></span>
                                    </button>
                                    <ul class="dropdown-menu" role="menu">
                                        <?php foreach ($events as $group => $event): ?>
                                            <li role="presentation" class="dropdown-header">
                                                <?php echo $group; ?>
                                            </li>
                                            <?php foreach ($event as $k => $e): ?>
                                                <li id="event_<?php echo $k; ?>">
                                                    <a data-toggle="ajaxmodal" data-target="#triggerEventModal" class="list-group-item" href="<?php echo $view['router']->path('mautic_pointtriggerevent_action', ['objectAction' => 'new', 'type' => $k, 'tmpl' => 'event', 'triggerId' => $sessionId]); ?>">
                                                        <div data-toggle="tooltip" title="<?php echo $e['description']; ?>">
                                                            <span><?php echo $e['label']; ?></span>
                                                        </div>
                                                    </a>
                                                </li>
                                            <?php endforeach; ?>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            </div>
                            <?php
                            foreach ($triggerEvents as $event):
                                $template = (isset($event['settings']['template'])) ? $event['settings']['template'] :
                                    'MauticPointBundle:Event:generic.html.php';
                                echo $view->render($template, [
                                    'event'     => $event,
                                    'id'        => $event['id'],
                                    'deleted'   => in_array($event['id'], $deletedEvents),
                                    'sessionId' => $sessionId,
                                ]);
                            endforeach;
                            ?>
                            <?php if (!count($triggerEvents)): ?>
                                <div class="alert alert-info" id="triggerEventPlaceholder">
                                    <p><?php echo $view['translator']->trans('mautic.point.trigger.addevent'); ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 bg-white height-auto bdr-l">
        <div class="pr-lg pl-lg pt-md pb-md">
            <?php
            echo $view['form']->row($form['category']);
            echo $view['form']->row($form['isPublished']);
            echo $view['form']->row($form['publishUp']);
            echo $view['form']->row($form['publishDown']);
            ?>
        </div>
    </div>
</div>
<?php echo $view['form']->end($form); ?>

 <?php
    $view['slots']->append('modal', $this->render('MauticCoreBundle:Helper:modal.html.php', [
        'id'            => 'triggerEventModal',
        'header'        => $view['translator']->trans('mautic.point.trigger.form.modalheader'),
        'footerButtons' => true,
    ]));
?>
