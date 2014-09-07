<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

$view->extend('MauticCoreBundle:Default:content.html.php');
$view['slots']->set('mauticContent', 'campaign');

$header = ($entity->getId()) ?
    $view['translator']->trans('mautic.campaign.header.edit',
        array('%name%' => $view['translator']->trans($entity->getName()))) :
    $view['translator']->trans('mautic.campaign.header.new');
$view['slots']->set("headerTitle", $header);
?>

<div class="row bundle-content-container">
    <div class="col-xs-12 col-sm-8 bundle-main bundle-main-left auto-height">
        <div class="rounded-corners body-white bundle-main-inner-wrapper scrollable padding-md">
            <?php echo $view['form']->start($form); ?>
            <?php
            echo $view['form']->row($form['campaigns-panel-wrapper-start']);
            echo $view['form']->row($form['details-panel-start']);
            echo $view['form']->row($form['name']);
            echo $view['form']->row($form['description']);
            echo $view['form']->row($form['category_lookup']);
            echo $view['form']->row($form['category']);
            echo $view['form']->row($form['triggerExistingLeads']);
            echo $view['form']->row($form['isPublished']);
            echo $view['form']->row($form['publishUp']);
            echo $view['form']->row($form['publishDown']);
            echo $view['form']->row($form['details-panel-end']);
            echo $view['form']->row($form['events-panel-start']);
            ?>
            <div id="campaignEvents">
                <?php
                $level = 1;
                if (count($campaignEvents)):
                foreach ($campaignEvents as $event):
                    $template = (isset($event['settings']['template'])) ? $event['settings']['template'] :
                        'MauticCampaignBundle:Event:generic.html.php';
                    echo $view->render($template, array(
                        'event'  => $event,
                        'inForm'  => true,
                        'id'      => $event['id'],
                        'deleted' => in_array($event['id'], $deletedEvents)
                    ));
                endforeach;
                else:
                ?>
                <h3 id='campaign-event-placeholder'><?php echo $view['translator']->trans('mautic.campaign.form.addevent'); ?></h3>
                <?php endif; ?>
            </div>
            <?php
            echo $view['form']->row($form['events-panel-end']);
            echo $view['form']->row($form['campaigns-panel-wrapper-end']);
            echo $view['form']->end($form);
            ?>
            <div class="footer-margin"></div>
        </div>
    </div>

    <div class="col-xs-12 col-sm-4 bundle-side bundle-side-right auto-height">
        <div class="rounded-corners body-white bundle-side-inner-wrapper scrollable padding-md">
            <?php $view['slots']->output('_content'); ?>
        </div>
    </div>


    <?php
    $view['slots']->start('modal');
    echo $this->render('MauticCoreBundle:Helper:modal.html.php', array(
        'id'     => 'campaignEventModal',
        'header' => $view['translator']->trans('mautic.campaign.form.modalheader'),
    ));
    $view['slots']->stop();
    ?>
</div>