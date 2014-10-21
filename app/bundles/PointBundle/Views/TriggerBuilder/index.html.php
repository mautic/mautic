<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

$view->extend('MauticCoreBundle:Default:content.html.php');
$view['slots']->set('mauticContent', 'pointTrigger');

$header = ($entity->getId()) ?
    $view['translator']->trans('mautic.point.trigger.header.edit',
        array('%name%' => $view['translator']->trans($entity->getName()))) :
    $view['translator']->trans('mautic.point.trigger.header.new');
$view['slots']->set("headerTitle", $header);
?>

<!-- start: box layout -->
<div class="box-layout">
    <!-- container -->
    <div class="col-md-9 bg-auto height-auto bdr-r">
        <div class="row">
            <div class="col-md-6">
                <div class="pa-md">
                    <?php echo $view['form']->start($form); ?>

                    <?php
                    echo $view['form']->row($form['name']);
                    echo $view['form']->row($form['description'], array('attr' => array('class' => 'form-control editor')));
                    ?>
                </div>
            </div>
            <div class="col-md-6">
                <div class="pa-md">
                    <div id="triggerEvents">
                        <?php
                        if (!count($triggerEvents)):
                        foreach ($triggerEvents as $event):
                            $template = (isset($event['settings']['template'])) ? $event['settings']['template'] :
                                'MauticPointBundle:Event:generic.html.php';
                            echo $view->render($template, array(
                                'event'  => $event,
                                'inForm'  => true,
                                'id'      => $event['id'],
                                'deleted' => in_array($event['id'], $deletedEvents)
                            ));
                        endforeach;
                        else:
                        ?>
                            <h3 id='trigger-event-placeholder'><?php echo $view['translator']->trans('mautic.point.trigger.form.addevent'); ?></h3>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 bg-white height-auto">
        <div class="pr-lg pl-lg pt-md pb-md">
            <?php
                echo $view['form']->row($form['points']);
                echo $view['form']->row($form['color']);
                echo $view['form']->row($form['triggerExistingLeads']);
                echo $view['form']->row($form['category_lookup']);
                echo $view['form']->row($form['category']);
                echo $view['form']->row($form['isPublished']);
                echo $view['form']->row($form['publishUp']);
                echo $view['form']->row($form['publishDown']);
            ?>
        </div>
    </div>
</div>
 <?php
    $view['slots']->append('modal', $this->render('MauticCoreBundle:Helper:modal.html.php', array(
        'id'     => 'triggerEventModal',
        'header' => $view['translator']->trans('mautic.point.trigger.form.modalheader'),
    )));
?>

<?php echo $view['form']->end($form); ?>